<?php

namespace App\Services\Database;

use App\Enums\InvoiceStatus;
use App\Enums\OfferStatus;
use App\Models\Contact;
use App\Models\Industry;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Lead;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Client;
use App\Zizaco\Entrust\Entrust;
use Carbon\Carbon;
use Faker\Generator as Faker;

class DatabaseService
{

    protected $faker;

    public function __construct(Faker $faker) {
        $this->faker = $faker;
    }

    public function getAllTables(){
        return \DB::select('SHOW TABLES');
    }

    public function cleanTables(array $excludeTables = null)
    {
        if (is_null($excludeTables)) {
            $excludeTables = explode(',', env('TSY_IZY', ''));
        }
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $tables = \DB::select('SHOW TABLES');
        $tables = array_map('current', $tables);

        foreach ($tables as $table) {
            if (!in_array($table, $excludeTables)) {
                \DB::table($table)->truncate();
            }
        }
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }


    // $datas = \DB::table($table)->get();  --> table atao ety ivelany
    function getIdByParameter($table, $parametre) {
        $result = [];
        foreach($table as $data) {
            $result[$data->$parametre] = $data->id;
        }
        return $result;
    }


    // feuille 1 -> projet_title & client_name
    public function importDataToDB($file1, $file2, $file3): array
    {
        $errors = [];
        $warnings = [];

        try {
            \DB::beginTransaction();

            info("Debut import fichier 1");
            $handle = fopen($file1, 'r');
            $header = fgetcsv($handle, 1000, ',');
            $lineNumber = 1;
            $row1 = [];

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNumber++;
                $row = array_combine($header, $data);

                try {
                    // doublon
//                    $dataImploded = implode(",", $data);
//                    if(in_array($dataImploded, $row1)) {
//                        throw new \Exception("Doublon détecté");
//                    }
//                    $row1[] = $dataImploded;

                    // creation user
                    $userId = User::inRandomOrder()->value('id') ?? factory(User::class)->create()->id;
                    $industryId = Industry::inRandomOrder()->value('id') ?? factory(Industry::class)->create()->id;

                    if($row["project_title"] == "") {
                        throw new \Exception("Le titre du projet est nécessaire");
                    }
                    if($row["client_name"] == "") {
                        throw new \Exception("Le nom du client est nécessaire");
                    }

                    $client = Client::firstOrCreate(
                        ['company_name' => $row['client_name']],
                        [
                            'external_id' => $this->faker->uuid,
                            'address' => $this->faker->address,
                            'zipcode' => $this->faker->postcode,
                            'city' => $this->faker->city,
                            'company_type' => 'ApS',
                            'industry_id' => $industryId,
                            'user_id' => $userId
                        ],
                    );

                    $contact = Contact::firstOrCreate(
                        ['client_id' => $client->id],
                        [
                            'external_id' => $this->faker->uuid,
                            'name' => $this->faker->name,
                            'email' => $this->faker->email,
                            'primary_number' => $this->faker->randomNumber(8),
                            'secondary_number' => $this->faker->randomNumber(8),
                            'is_primary' => 1,
                        ]
                    );

                    // creation projet
                    $userAssignedId = User::inRandomOrder()->value('id');
                    $userCreatedId = User::inRandomOrder()->value('id');

                    Project::create([
                        'external_id' => $this->faker->uuid,
                        'title' => $row['project_title'],
                        'description' => $this->faker->paragraph(10),
                        'status_id' => 11,
                        'user_assigned_id' => $userAssignedId,
                        'user_created_id' => $userCreatedId,
                        'client_id' => $client->id,
                        'deadline' => Carbon::now()->addDays(7)
                    ]);

                } catch (\Exception $e) {
                    if(str_contains($e->getMessage(), 'Doublon')) {
                        $warnings[] = sprintf("Ligne %d fichier 1: %s", $lineNumber, $e->getMessage());
                    } else {
                        $errors[] = sprintf("Ligne %d fichier 1: %s", $lineNumber, $e->getMessage());
                    }
                }
            }
            fclose($handle);

            info("Debut import fichier 2");
            $handle2 = fopen($file2, 'r');
            $header2 = fgetcsv($handle2, 1000, ',');
            $lineNumber2 = 1;

            $allProjects = \DB::table('projects')->get();
            $projetIds = $this->getIdByParameter($allProjects, 'title');

            $row2 = [];

            while (($data = fgetcsv($handle2, 1000, ',')) !== false) {
                $lineNumber2++;
                $row = array_combine($header2, $data);

                try {
                    // doublon
//                    $dataImploded = implode(",", $data);
//                    if(in_array($dataImploded, $row2)) {
//                        throw new \Exception("Doublon détecté");
//                    }
//                    $row2[] = $dataImploded;

                    // fafana condition raha tsy mverifier hoe tsy maintsy misy ve le projet , si vide -> null
                    if($projetIds[$row["project_title"]] == null) {
                        throw new \Exception("le projet est inexistant");
                    }
                    if($row["task_title"] == "") {
                        throw new \Exception("Le titre de la tâche est nécessaire");
                    }

                    $userAssignedId = User::inRandomOrder()->value('id');
                    $userCreatedId = User::inRandomOrder()->value('id');
                    $clientId = Client::inRandomOrder()->value('id');

                    Task::create([
                        'title' => $row["task_title"],
                        'description' => $this->faker->paragraph(5),
                        'external_id' => $this->faker->uuid,
                        'status_id' => 1,
                        'user_assigned_id' => $userAssignedId,
                        'user_created_id' => $userCreatedId,
                        'client_id' => $clientId,
                        'deadline' => Carbon::now()->addDays(7),
                        'project_id' => $projetIds[$row["project_title"]],
                    ]);

                } catch (\Exception $e) {
                    if(str_contains($e->getMessage(), 'Doublon')) {
                        $warnings[] = sprintf("Ligne %d fichier 2: %s", $lineNumber, $e->getMessage());
                    } else {
                        $errors[] = sprintf("Ligne %d fichier 2: %s", $lineNumber2, $e->getMessage());
                    }
                }
            }

            fclose($handle2);

            info("Debut import fichier 3");
            $handle3 = fopen($file3, 'r');
            $header3 = fgetcsv($handle3, 1000, ',');
            $lineNumber3 = 1;

            $clients = \DB::table('clients')->get();
            $clientIds = $this->getIdByParameter($clients, 'company_name');

            $row3 = [];

            while (($data = fgetcsv($handle3, 1000, ',')) !== false) {
                $lineNumber3++;
                $row = array_combine($header3, $data);

                try {
                    // doublon
//                    $dataImploded = implode(",", $data);
//                    if(in_array($dataImploded, $row3)) {
//                        throw new \Exception("Doublon détecté");
//                    }
//                    $row3[] = $dataImploded;

                    if($row['client_name'] == "" || $clientIds[$row["client_name"]] == null) {
                        throw new \Exception("le client est inexistant");
                    }
                    if(!is_numeric($row['prix'])) {
                        throw new \Exception("Le prix doit être un nombre");
                    }
                    if(!is_numeric($row['quantite'])) {
                        throw new \Exception("La quantité doit être un nombre");
                    }
                    if($row['prix'] < 0) {
                        throw new \Exception("Le prix ne doit pas être négatif");
                    }
                    if($row['quantite'] < 0) {
                        throw new \Exception("La quantité ne doit pas être négatif");
                    }
                    if($row['prix'] == 0) {
                        throw new \Exception("Le prix ne doit pas être égal à 0");
                    }
                    if($row['quantite'] == 0) {
                        throw new \Exception("La quantité ne doit pas être égal à 0");
                    }

                    $userAssignedId = User::inRandomOrder()->value('id');
                    $userCreatedId = User::inRandomOrder()->value('id');

                    $lead = Lead::firstOrCreate(
                        ['title' => $row['lead_title']],
                        [
                            'external_id' => $this->faker->uuid,
                            'description' => $this->faker->paragraph(5),
                            'status_id' => 7,
                            'user_assigned_id' => $userAssignedId,
                            'user_created_id' => $userCreatedId,
                            'client_id' => $clientIds[$row['client_name']],
                            'deadline' => $this->faker->dateTimeBetween('2025-03-27', '2025-04-15')
                        ]
                    );

                    $produit = Product::firstOrCreate(
                        ['name' => $row['produit']],
                        [
                            'external_id' => $this->faker->uuid,
                            'description' => $this->faker->paragraph(5),
                            'number' => 10000,
                            'default_type' => 'pieces',
                            'archived' => 0,
                            'price' => $row['prix']
                        ]
                    );

                    if($row['type'] == 'invoice') {
                        $invoice = Invoice::create([
                            'status' => InvoiceStatus::draft()->getStatus(),
//                            'sent_at' => Carbon::now(),
                            'due_at' => Carbon::now()->addDays(10),
                            'client_id' => $clientIds[$row['client_name']],
                            'source_id' => $lead->id,
                            'source_type' => 'App\Models\Lead',
                            'external_id' => $this->faker->uuid,
//                            'offer_id' => $offer->id,
                        ]);

                        $invoice_line2 = InvoiceLine::create([
                            'external_id' => $this->faker->uuid,
                            'type' => $produit->default_type,
                            'quantity' => $row['quantite'],
                            'title' => $produit->name,
                            'price' => $row['prix'],
                            'product_id' => $produit->id,
                            'invoice_id' => $invoice->id,
                        ]);

                    } else {
                        $offer = Offer::create([
                            'status' => $row['type'] == 'invoice' ? OfferStatus::won()->getStatus() : OfferStatus::inProgress()->getStatus(),
//                        'sent_at' => Carbon::now(),
//                        'due_at' => Carbon::now()->addDays(7),
                            'client_id' => $clientIds[$row['client_name']],
                            'source_id' => $lead->id,
                            'source_type' => 'App\Models\Lead',
                            'external_id' => $this->faker->uuid
                        ]);

                        $invoice_line1 = InvoiceLine::create([
                            'external_id' => $this->faker->uuid,
                            'type' => $produit->default_type,
                            'quantity' => $row['quantite'],
                            'title' => $produit->name,
                            'price' => $row['prix'],
                            'product_id' => $produit->id,
                            'offer_id' => $offer->id,
                        ]);
                    }

                } catch (\Exception $e) {
                    if(str_contains($e->getMessage(), 'Doublon')) {
                        $warnings[] = sprintf("Ligne %d fichier 3: %s", $lineNumber, $e->getMessage());
                    } else {
                        $errors[] = sprintf("Ligne %d fichier 3: %s", $lineNumber3, $e->getMessage());
                    }
                }
            }

            fclose($handle3);

//            if(!empty($warnings)){
//                \DB::rollback();
//            }

            if (empty($errors)) {
                \DB::commit();
                info("Importé avec succes");
            } else {
                \DB::rollBack();
                info("Import failed with ".count($errors)." errors");
            }

        } catch (\Throwable $e) {
            \DB::rollBack();
            $errors[] = "System error: ".$e->getMessage();
            info("Import system failure: ".$e->getMessage());
        }

        return [$errors, $warnings];
    }

    // si ca change en ce que j'ai dit, offer et invoice se transforme en firstOrCreate
}