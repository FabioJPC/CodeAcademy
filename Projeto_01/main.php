<?php
    include_once("functions.php");

    echo "------GERENCIADOR DE ALUGUEL DE VEÍCULOS------\n";
    $choice = 0;
    $db = getData();
    do{
        echo "1. Cadastrar novo veículo\n";
        echo "2. Meus veículos\n";
        echo "3. Buscar por um veículo\n";
        echo "4. Editar um veículo\n";
        echo "5. Remover veículo\n";
        echo "6. Alugar veículo\n";
        echo "7. Devolver veículo\n";
        echo "8. Minhas estatísticas\n";
        echo "0. Sair da aplicação\n";
        $choice = trim(fgets(STDIN));
        switch($choice){
            case 1:
                $userInput = getUserInput();
                if(validateUserInput($userInput)){
                    addCar($userInput, $db);  
                }else{
                    echo "Corrija os erros...\n\n";
                }
                break;

            case 2:
                showAllCars($db);
                break;

            case 3:
                echo "Digite o nome ou placa do carro que deseja buscar: ";
                $searchTerm = trim(fgets(STDIN));
                $searchResult = searchCar($searchTerm, $db);
                if($searchResult !== null) showEspecificCar($searchResult, $db);
                break; 
                
            case 4:
                echo "Digite o nome ou placa do carro que deseja alterar: ";
                $searchTerm = trim(fgets(STDIN));
                $searchResult = searchCar($searchTerm, $db);
                if($searchResult !== null) editCar($searchResult, $db);
                break;

            case 5:
                echo "Digite o nome ou placa do carro que deseja deletar: ";
                $searchTerm = trim(fgets(STDIN));
                $searchResult = searchCar($searchTerm, $db);
                if($searchResult !== null) deleteCar($searchResult, $db);
                break;

            case 6:
                echo "Qual veículo deseja alugar?(nome ou placa)\n";
                $searchTerm = trim(fgets(STDIN));
                $searchResult = searchCar($searchTerm, $db);
                if($searchResult !== null){
                    if($db[$searchResult]["isAvailable"] == true){
                        rentCar($searchResult, $db);
                    }else{
                        printErrorMessage("Esse carro já está alugado!\n");
                    }
                }
                break;

            case 7:
                echo "Qual veículo deseja devolver?(nome ou placa)\n";
                $searchTerm = trim(fgets(STDIN));
                $searchResult = searchCar($searchTerm, $db);
                if($searchResult !== null){
                    if($db[$searchResult]["isAvailable"] == false){
                        unRentCar($searchResult, $db);
                    }else{
                        printErrorMessage("Esse carro não está alugado!\n");
                    }
                }
                break;

            case 8:
                showStatistics($db);
                break;

            case 0: 
                echo "Saindo da aplicação...\n";
                break;

            default:
                printErrorMessage("Opção inválida, selecione novamente...");
                break;
        }
    }while($choice != 0);
