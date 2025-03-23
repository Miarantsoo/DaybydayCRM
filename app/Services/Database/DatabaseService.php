<?php

namespace App\Services\Database;

use App\Zizaco\Entrust\Entrust;

class DatabaseService
{

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

    function importIndustry($filename)
    {
        Log::info('Importing industry data from file: ' . $filename);

        // Open the CSV file
        $errors = [];

        if (($handle = fopen($filename, 'r')) !== false) {
            // Read the header row
            $header = fgetcsv($handle, 1000, ';');

            // Create a temporary table
            \DB::statement('CREATE TEMPORARY TABLE temp_industries LIKE industries');

            // Loop through the file line by line
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                // Create an associative array with the header as keys
                $row = array_combine($header, $data);

                try {
                    // Insert the data into the temporary table
                    \DB::table('temp_industries')->insert([
                        'external_id' => $row['external_id'],
                        'name' => $row['name'],
                    ]);
                } catch (\Exception $e) {
                    // If there's an error, stock it inside the errors array
                    $errors[] = $e->getMessage();
                }
            }

            // Close the file
            fclose($handle);

            // If there are no errors, copy data from the temporary table to the main table
            if (empty($errors)) {
                \DB::statement('INSERT INTO industries SELECT * FROM temp_industries');
            }
        }

        // Return errors if any
        if (!empty($errors)) {
            return $errors;
        }
    }
}