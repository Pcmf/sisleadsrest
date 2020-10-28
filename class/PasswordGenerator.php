<?php


function gerarPassword($num_caracteres = 8) {

        $password = "";

        // variável para definir quais o caractéres possíveis para a password

        $possiveis = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";

        // para verificar quantos caractéres diferentes existem para gerar uma password

        $max = strlen($possiveis);

        // a password não pode ser ter mais caractéres do que os que foram predefinidos para $possiveis    

        if ($num_caracteres > $max) {

            $num_caracteres = $max;
        }

        // variável de incrementação para saber quantos caratéres já tem a password enquanto está a ser gerada

        $i = 0;

        // adiciona caracteres a $password até $num_caracteres estar completo    

        while ($i < $num_caracteres) {

            // escolhe um caracter dos possiveis

            $char = substr($possiveis, mt_rand(0, $max - 1), 1);

            // verificar se o caracter escolhido já está na $password?

            if (!strstr($password, $char)) {

                // se não estiver incluido adiciona o novo caracter...         

                $password .= $char;

                // ... e incrementa a variável $i        

                $i++;
            }
        }

        return $password;
    }
