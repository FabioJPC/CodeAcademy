<?php
    const DB_PATH = "database.json";

    const TRANSLATION = [
        "brand" => "Marca",
        "model" => "Modelo",
        "year" => "Ano",
        "plate" => "Placa",
        "pricePerDay" => "Preço por dia",
        "pricePerMonth" => "Preço por mês",
        "name" => "Nome",
        "phone" => "Telefone",
    ];

    //DATABASE
    function syncDatabase(array $db): bool{
        $formattedArray = json_encode($db, JSON_PRETTY_PRINT);
        return file_put_contents(DB_PATH, $formattedArray) !== false;
    }

    function getData(): array{
        if(!file_exists(DB_PATH)){
            file_put_contents(DB_PATH, json_encode([]));
            return [];
        }
        $database = file_get_contents(DB_PATH);
        $database = json_decode($database, true);
        return $database;
    }

    //CRUD
    function addCar(array $carData, array &$db): bool{
        if(empty($db)){
            $carData["id"] = 10000000;
        }else{
            $lastCar = end($db);
            $carData["id"] = $lastCar["id"] + 1; //AUTOINCREMENT
        }
        $db[] = $carData;
        syncDatabase($db);
        printSuccessMessage("Veículo adicionado com sucesso!\n");
        return true;
    }

    function searchCar(string $searchTerm, array $db){
        $results = [];
        $searchTerm = mb_strtoupper(trim($searchTerm));

        foreach($db as $index => $car){
            $modelName = mb_strtoupper($car["model"]);
            $plate = $car["plate"];
            if(str_contains($modelName, $searchTerm) || $plate == $searchTerm){
                $results[] = $index;
                continue;
            }

            $nameWords = explode(" ", $modelName);
            foreach($nameWords as $word){
                if(levenshtein($word, $searchTerm) <= 2){
                    $results[] = $index;
                    break;
                }
            }
        }
        if(count($results) > 1){
            printSuccessMessage("Foram encontrados vários resultados, em qual deles está interessado?");
            $selection = selectFromMultiple($results, $db);
            if($selection == -1){
                return null;
            }else{
                return $results[$selection];
            }
        }
        if(empty($results)){
            printErrorMessage("Nenhum veículo encontrado...");
            return null;
        }
        return $results[0];
    }

    function editCar(int $carToEdit, array &$db):bool{
        $editedCar = $db[$carToEdit];
        echo "Editando " . $editedCar["model"] . " " . $editedCar["plate"] . "\n";
        echo "Edite os dados ou aperte 'Enter' para mantê-los\n";

        foreach($editedCar as $key => $value){
            //Non editable or function especific
            if($key == "id" || $key == 'renter' || $key == "isAvailable") continue;
            
            printf("%s: [%s]\n", TRANSLATION[$key], $value);
                $buffer = trim(fgets(STDIN));
                if($buffer != ""){
                    $editedCar[$key] = $buffer;
                }    
        }
        if(!validateUserInput($editedCar)){
                printErrorMessage("Os dados inseridos são inválidos, abortando edição...");
                return false;
            }
        $db[$carToEdit] = $editedCar;
        syncDatabase($db);
        printSuccessMessage("Veículo editado com sucesso!");
        return true;
    }

    function deleteCar(int $carIndex, array &$db):bool{
        $carToDelete = $db[$carIndex];
        echo "Tem certeza que deseja deletar o veículo: \n";
        printf("%s %s com a placa: %s \n", 
            $carToDelete["brand"], 
            $carToDelete["model"], 
            $carToDelete["plate"]);
        echo "1. Sim \n2. Não\n";
        $selection = trim(fgets(STDIN));
        if($selection == 1){
            unset($db[$carIndex]);
            $db = array_values($db);
            syncDatabase($db);
            printSuccessMessage("Vaículo removido com sucesso!");
            return true;
        }
        printErrorMessage("Abortando remoção...");
        return false;
    }

    // I/O
    function showAllCars(array $data){
        if(empty($data)){
            printErrorMessage("Nenhum carro para exibir");
            return;
        }
        $sortedData = $data;
        uasort($sortedData, function($a, $b){
            return strcmp($a["model"], $b["model"]);
        }); 
        foreach($sortedData as $car){
            echo "\n";
            echo "ID: " . $car["id"] . "\n";
            printf("Veículo: %s (%s)\n", $car["model"], $car["brand"]);
            echo "Ano: " . $car["year"] . "\n";
            echo "Placa: " . $car["plate"] . "\n";
            echo "Preço diária: R$" . $car["pricePerDay"] . "\n";
            echo "Preço mensal: R$" . $car["pricePerMonth"] . "\n";
            echo "Está disponível: " . (($car["isAvailable"]) ? "Sim" : "Não");
            echo "\n--------------\n";
        }
    }

    function showEspecificCar(int $index, array $db){
        if (!isset($db[$index])) {
            printErrorMessage("Veículo não encontrado.");
            return;
        }
        $car = $db[$index];
        echo "\n";
        echo "ID: " . $car["id"] . "\n";
        printf("Veículo: %s (%s)\n", $car["model"], $car["brand"]);
        echo "Ano: " . $car["year"] . "\n";
        echo "Placa: " . $car["plate"] . "\n";
        echo "Preço diária: R$" . $car["pricePerDay"] . "\n";
        echo "Preço mensal: R$" . $car["pricePerMonth"] . "\n";
        echo "Está disponível: " . (($car["isAvailable"]) ? "Sim" : "Não") . "\n";
        if(!$car["isAvailable"]){
            echo "Alugado para: " . $car["renter"]["name"];
            echo " | Telefone: " . $car["renter"]["phone"] . "\n";
        }
        echo "\n--------------\n";
    }

    function getUserInput():array{
        $newCar = [];
        echo "---Digite os dados do novo veículo---\n";
        echo "Marca: ";
        $newCar["brand"] = trim(fgets(STDIN));
        echo "Modelo : ";
        $newCar["model"] = trim(fgets(STDIN));
        echo "Ano: ";
        $newCar["year"] = (int) trim(fgets(STDIN));
        echo "Placa: ";
        $newCar["plate"] = strtoupper(trim(fgets(STDIN)));
        echo "Preço (diária): ";

        $rawPriceDay = trim(fgets(STDIN));
        $sanitizedPriceDay = str_replace(",", ".", $rawPriceDay);
        $newCar["pricePerDay"] = (double) $sanitizedPriceDay;

        echo "Preço (mensal): ";
        $rawPriceMonth = trim(fgets(STDIN));
        $sanitizedPriceMonth = str_replace(",", ".", $rawPriceMonth);
        $newCar["pricePerMonth"] = (double) $sanitizedPriceMonth;

        $newCar["isAvailable"] = true;
        $newCar["renter"] = null;
        return $newCar;
    }

    function printErrorMessage(string $message){
        echo "\033[31m$message\033[0m\n";
    }

    function printSuccessMessage(string $message){
        echo "\033[32m$message\033[0m\n";
    }

    // Renting procedures
    function rentCar(int $carToRent, &$db){
        echo "Digite o nome do cliente: ";
        $renter["name"] = trim(fgets(STDIN));

        echo "\nTelefone: ";
        $renter["phone"] = trim(fgets(STDIN));

        echo "\nQuantos dias o carro ficará alugado?: ";
        $renter["days"] = (int) trim(fgets(STDIN));

        $db[$carToRent]["isAvailable"] = false;
        $db[$carToRent]["renter"] = $renter;
        
        syncDatabase($db);
        printSuccessMessage("Veículo alugado com sucesso\n");
    }

    function unRentCar(int $carToUnrent, &$db){
        $db[$carToUnrent]["isAvailable"] = true;
        $db[$carToUnrent]["renter"] = null;
        syncDatabase($db);
        printSuccessMessage("Veículo devolvido com sucesso\n");
    }

    //MISC
    function validateUserInput(array $car): bool{
        $noErrors = true;
        if(!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $car["brand"])){
            printErrorMessage("O nome da marca é inválido. Deve ser somente letras e não pode ser vazio.");
            $noErrors = false;
        }
        if(strlen($car["model"]) < 2){
            printErrorMessage("O nome do modelo é inválido. Deve ser maior que 2 caracteres.");
            $noErrors = false;
        }
        if($car["year"]  < 1886 || $car["year"] > date('Y') + 1){
            printErrorMessage("Ano inválido");
            $noErrors = false;
        }
        if(!preg_match("/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/", $car["plate"])){
            printErrorMessage("Placa inválida");
            $noErrors = false;
        }
        if(!is_numeric($car["pricePerDay"])){
            printErrorMessage("Valor da diária inválida");
            $noErrors = false;
        }
        if(!is_numeric($car["pricePerMonth"])){
            printErrorMessage("Valor da mensalidade inválido");
            $noErrors = false;
        }
        return $noErrors;
    } 

    function selectFromMultiple(array $searchResults, array $db):int{
        /*  Receives an array of search results indexes
            Prompt the user to chose one
            Return the index in database of the selected value */ 

        $counter = 1;   //Human readable index
        foreach($searchResults as $indexInDb){
            $car = $db[$indexInDb];
            echo $counter . ". " . $car["model"] . "    |    " . $car["plate"] . "\n";
            $counter++;
        }
        $selection = (int) trim(fgets(STDIN));
        if($selection < 1 || $selection >= $counter){
            printErrorMessage("Opção inválida... abortando.\n");
            return -1;
        }
        return $selection - 1;
    }

    function showStatistics(array $db){
        if($db == []){
            echo "Nenhuma estatística disponível no momento...\n";
            return;
        }
        $totalCars = 0;
        $rentedCars = 0;
        $avgAge = 0;
        $avgPriceDay = 0;
        $avgPriceMonth = 0;
        $totalToReceive = 0;

        foreach($db as $car){
            $totalCars++;
            if($car["isAvailable"] == false){
                $rentedCars++;
                $rentInfo = $car["renter"];
                if($rentInfo["days"] < 30){
                    $totalToReceive += ($rentInfo["days"] * $car["pricePerDay"]);
                }else{
                    $months = $rentInfo["days"] / 30;
                    $days = $rentInfo["days"] % 30;
                    $total = ($months * $car["pricePerMonth"]) + ($days * $car["pricePerDay"]);
                    $totalToReceive += $total;
                }
                
            } 
            $avgAge += (date("Y") - $car["year"]);
            $avgPriceDay += $car["pricePerDay"];
            $avgPriceMonth += $car["pricePerMonth"];
        }
        $alocationPercentage = ($rentedCars / $totalCars) * 100;
        $avgAge /= $totalCars;
        $avgPriceDay /= $totalCars;
        $avgPriceMonth /= $totalCars;

        echo "\n----Estatísticas-----\n";
        printf("Total de carros cadastrados: %d\n", $totalCars);
        printf("Alocação: %s%% | %d de %d alugados\n",
                number_format($alocationPercentage, 1), 
                $rentedCars,
                $totalCars);
        printf("Idade média da frota: %s anos\n", number_format($avgAge, 1));
        printf("Preço médio por dia: R$%s\n", number_format($avgPriceDay));
        printf("Preço médio por mês: R$%s\n", number_format($avgPriceMonth));
        printf("Total a receber dos carros alugados: R$%s\n", number_format($totalToReceive, 2));
        echo "--------------------\n";

    }
