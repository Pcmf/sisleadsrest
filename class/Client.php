<?php

/**
 * Description of Client
 *  This class provides all information about the client, including the login.
 *  It has also the hablility to insert and update
 *
 * @author pedro
 */

require_once 'passwordHash.php';
include_once 'PasswordGenerator.php';
include_once 'sendEmail.php';

class Client
{
    private $db;
    private $Lead;

    public function __construct()
    {
        $this->db = new DB();
        $this->Lead = new Lead();
    }


    /**
     * LOGIN
     * @param type $email
     * @param type $pass
     * @return string token
     */
    public function login($email, $pass)
    {
        $res = array();
        //Get all active users that have this email
        if ($resp = $this->db->query("SELECT * FROM cad_clientes WHERE email=:email AND ativo=1 ", array(':email' => $email))) {

            $this->valid = false;
            //Check against password supplied
            foreach ($resp as $r) {
                if (passwordHash::check_password($r['password'], $pass)) {
                    $this->token = $this->generateToken($r);
                    $this->db->query(
                        "UPDATE cad_clientes SET token=:token, ultimoacesso=NOW(), numacessos=numacessos+1 WHERE id=:id ",
                        array(':token' => $this->token, ':id' => $r['id'])
                    );
                    $this->valid = true;
                    break;
                }
            }
            if ($this->valid) {
                return $res['resp'] = $this->token;
            } else {
                return $res['resp'] = false;
            }
        }
    }
    /**
     * Description:  Returns personal information about client associated with the lead passed by param
     * @param type $lead
     * @return array
     */
    public function getClient($lead)
    {
        return $this->db->query(
            "SELECT P.lead, L.status, P.nome, P.nif, P.email, P.telefone, P.idade, P.profissao, P.vencimento,"
                . " P.tipocontrato, P.mesinicio, P.anoinicio, P.estadocivil, P.tipohabitacao, P.valorhabitacao, P.anoiniciohabitacao, P.segundoproponente, "
                . " P.relacaofamiliar, P.parentesco2,  P.profissao2, P.vencimento2,  P.mesinicio2, P.anoinicio2, "
                . " P.tipocontrato2, P.tipohabitacao2, P.valorhabitacao2, P.anoiniciohabitacao2, P.mesmahabitacao, P.finalidade, "
                . " F.datanascimento, F.nacionalidade "
                . " FROM arq_processo P "
                . " INNER JOIN arq_leads L ON L.id=P.lead "
                . " LEFT JOIN arq_process_form F ON F.lead=P.lead"
                . " WHERE P.lead=:lead",
            [':lead' => $lead]
        );
    }

    public function updateStatus($lead, $status)
    {
        try {
            $this->db->query(
                "UPDATE arq_leads SET status=:status WHERE id=:lead ",
                [
                    ':status' => $status,
                    ':lead' => $lead
                ]
            );
            return;
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function getStatus($lead)
    {
        return $this->db->query("SELECT status FROM arq_leads WHERE id=:lead",
            [
                ':lead' => $lead
            ]
        )[0]['status'];
    }

    public function insertClient($obj)
    {
        $obj = $this->checkFields(0, $obj);
        try {
            $dateTime = new DateTime();
            $timeStamp = $dateTime->getTimestamp();
            $user = $this->Lead->getRandomGestor();
            $this->db->query(
                "INSERT INTO arq_leads(idleadorig, nomelead, fornecedor, tipo, nome, email, telefone, montante, prazopretendido, status, datastatus, user) "
                    . " VALUES(:idleadorig, 'AC', 99, :tipo, :nome, :email, :telefone, :montante, :prazopretendido, 37, NOW(), :user)",
                [
                    ':idleadorig' => $timeStamp,
                    ':tipo' => $obj->tipocredito,
                    ':nome' => $obj->nome,
                    ':email' => $obj->email,
                    ':telefone' => $obj->telefone,
                    ':montante' => $obj->montante,
                    ':prazopretendido' => $obj->prazopretendido,
                    ':user' => $user
                ]
            );
            $lead = $this->db->lastInsertId();
            // Insert to arq_processo
            $this->db->query(
                "INSERT INTO arq_processo(lead, nome, email, telefone, idade, tipocontrato, mesinicio, "
                    . " anoinicio, estadocivil, segundoproponente, relacaofamiliar, tipocontrato2, mesinicio2, anoinicio2,"
                    . "tipohabitacao, valorhabitacao, anoiniciohabitacao, mesmahabitacao, tipohabitacao2, valorhabitacao2, anoiniciohabitacao2, tipocredito) "
                    . " VALUES(:lead, :nome, :email, :telefone, :idade, :tipocontrato, :mesinicio, "
                    . " :anoinicio, :estadocivil, :segundoproponente, :relacaofamiliar, :tipocontrato2, :mesinicio2, :anoinicio2,"
                    . ":tipohabitacao, :valorhabitacao, :anoiniciohabitacao, :mesmahabitacao, :tipohabitacao2, :valorhabitacao2, :anoiniciohabitacao2, :tipocredito)",
                [
                    ':lead' => $lead, ':nome' => $obj->nome, ':email' => $obj->email, ':telefone' => $obj->telefone, ':idade' => $obj->idade,
                    ':tipocontrato' => $obj->tipocontrato, ':mesinicio' => $obj->mesinicio, ':anoinicio' => $obj->anoinicio, ':estadocivil' => $obj->estadocivil,
                    ':segundoproponente' => $obj->segundoproponente, ':relacaofamiliar' => $obj->relacaofamiliar, ':tipocontrato2' => $obj->tipocontarto,
                    ':mesinicio2' => $obj->mesinicio2, ':anoinicio2' => $obj->anoinicio2, ':tipohabitacao' => $obj->tipohabitacao, ':valorhabitacao' => $obj->valorhabitacao,
                    ':anoiniciohabitacao' => $obj->anoiniciohabitacao, ':mesmahabitacao' => $obj->mesmahabitacao, ':tipohabitacao2' => $obj->tipohabitacao,
                    ':valorhabitacao2' => $obj->valorhabitacao2, ':anoiniciohabitacao2' => $obj->anoiniciohabitacao2, ':tipocredito' => $obj->tipocredito
                ]
            );
            // Insert into arq_process_form
            $this->insertToProcessForm($lead, $obj);

            return $lead;
        } catch (\Throwable $th) {
            return $th;
        }
    }

    public function updateClient($lead, $obj)
    {
        $obj = $this->checkFields($lead, $obj);
        // Atualizar o status da lead para iniciado (37) pela AC se o status for < 8
        if ($this->getStatus($lead) < 8) {
            $status = 37;

        } elseif ($this->getStatus($lead) == 37) {
            $status = 38;
        } else {
            $status = $this->getStatus($lead);
        }
        if(!isset($obj->user)) {
            $user = $this->Lead->getRandomGestor();
        } else {
            $user = $obj->user;
        }

        // Verificar se tem gestor atribuido
        $gestor = $this->Lead->getGestor($lead);
        if (!$gestor) {
            $user = $this->Lead->getRandomGestor();
        } else {
            $user = $gestor;
        }


        //Update arq_leads
        $this->db->query(
            "UPDATE arq_leads SET tipo=:tipo, nome=:nome, email=:email, telefone=:telefone, montante=:montante, prazopretendido=:prazopretendido, "
            ." status=:status, datastatus=NOW(), user=:user "
                . " WHERE id=:lead ",
            [
                ':tipo' => $obj->tipocredito,
                ':nome' => $obj->nome, ':email' => $obj->email, ':telefone' => $obj->telefone,
                ':montante' => $obj->montante, ':prazopretendido' => $obj->prazopretendido,
                ':lead' => $lead,
                ':status'=>$status,
                ':user'=>$user
            ]
        );

        //Update arq_processo
        $this->db->query(
            "UPDATE arq_processo SET nome=:nome, email=:email, telefone=:telefone, idade=:idade, tipocontrato=:tipocontrato, mesinicio=:mesinicio, "
                . " anoinicio=:anoinicio, estadocivil=:estadocivil, segundoproponente=:segundoproponente, relacaofamiliar=:relacaofamiliar, "
                . " tipocontrato2=:tipocontrato2, mesinicio2=:mesinicio2, anoinicio2=:anoinicio2,"
                . " tipohabitacao=:tipohabitacao, valorhabitacao=:valorhabitacao, anoiniciohabitacao=:anoiniciohabitacao,"
                . " mesmahabitacao=:mesmahabitacao, tipohabitacao2=:tipohabitacao2, valorhabitacao2=:valorhabitacao2, anoiniciohabitacao2=:anoiniciohabitacao2, "
                . " tipocredito=:tipocredito, finalidade=:finalidade  "
                . " WHERE lead=:lead ",
            [
                ':nome' => $obj->nome, ':email' => $obj->email, ':telefone' => $obj->telefone, ':idade' => $obj->idade,
                ':tipocontrato' => $obj->tipocontrato, ':mesinicio' => $obj->mesinicio, ':anoinicio' => $obj->anoinicio, ':estadocivil' => $obj->estadocivil,
                ':segundoproponente' => $obj->segundoproponente, ':relacaofamiliar' => $obj->relacaofamiliar, ':tipocontrato2' => $obj->tipocontrato,
                ':mesinicio2' => $obj->mesinicio2, ':anoinicio2' => $obj->anoinicio2, ':tipohabitacao' => $obj->tipohabitacao, ':valorhabitacao' => $obj->valorhabitacao,
                ':anoiniciohabitacao' => $obj->anoiniciohabitacao, ':mesmahabitacao' => $obj->mesmahabitacao, ':tipohabitacao2' => $obj->tipohabitacao,
                ':valorhabitacao2' => $obj->valorhabitacao2, ':anoiniciohabitacao2' => $obj->anoiniciohabitacao2, ':tipocredito' => $obj->tipocredito,
                ':finalidade' => $obj->finalidade,
                ':lead' => $lead
            ]
        );
        $this->updateToProcessForm($lead, $obj);
    }


    private function insertToProcessForm($lead, $obj)
    {
        // Insert to arq_process_form
        $this->db->query(
            "INSERT INTO arq_process_form(lead, nome, telefone, email, datanascimento, nacionalidade, estadocivil, segundoproponente,"
                . " tipohabitacao, anoiniciohabitacao, valorhabitacao, tipohabitacao2, anoiniciohabitacao2, valorhabitacao2,"
                . " tipocontrato, desde, desdemes, tipocontrato2, desde2, desdemes2) "
                . " VALUES(:lead, :nome, :telefone, :email, :datanascimento, :nacionalidade, :estadocivil, :segundoproponente,"
                . " :tipohabitacao, :anoiniciohabitacao, :valorhabitacao, :tipohabitacao2, :anoiniciohabitacao2, :valorhabitacao2,"
                . " :tipocontrato, :desde, :desdemes, :tipocontrato2, :desde2, :desdemes2)",
            [
                ':lead' => $lead, ':nome' => $obj->nome, ':telefone' => $obj->telefone, ':email' => $obj->email, ':datanascimento' => $obj->datanascimento,
                ':nacionalidade' => $obj->nacionalidade, ':estadocivil' => $obj->estadocivil, ':segundoproponente' => $obj->segundoproponente,
                ':tipohabitacao' => $obj->tipohabitacao, ':anoiniciohabitacao' => $obj->anoiniciohabitacao, ':valorhabitacao' => $obj->valorhabitacao,
                ':tipohabitacao2' => $obj->tipohabitacao2, ':anoiniciohabitacao2' => $obj->anoiniciohabitacao2, ':valorhabitacao2' => $obj->valorhabitacao2,
                ':tipocontrato' => $obj->tipocontrato, ':desde' => $obj->anoinicio, ':desdemes' => $obj->mesinicio, ':tipocontrato2' => $obj->tipocontrato2,
                ':desde2' => $obj->anoinicio2, ':desdemes2' => $obj->mesinicio2
            ]
        );
    }

    private function updateToProcessForm($lead, $obj)
    {
        //Check if lead exist on arq_process_form to update or else insert
        if ($this->db->query("SELECT * FROM arq_process_form WHERE lead=:lead", [':lead' => $lead])) {
            // Update arq_process_form
            $this->db->query(
                "UPDATE arq_process_form SET nome=:nome, telefone=:telefone, email=:email, datanascimento=:datanascimento,"
                    . " nacionalidade=:nacionalidade, estadocivil=:estadocivil, segundoproponente=:segundoproponente,"
                    . " tipohabitacao=:tipohabitacao, anoiniciohabitacao=:anoiniciohabitacao, valorhabitacao=:valorhabitacao, tipohabitacao2=:tipohabitacao2,"
                    . " anoiniciohabitacao2=:anoiniciohabitacao2, valorhabitacao2=:valorhabitacao2,"
                    . " tipocontrato=:tipocontrato, desde=:desde, desdemes=:desdemes, tipocontrato2=:tipocontrato2, desde2=:desde2, desdemes2=:desdemes2 "
                    . " WHERE lead=:lead ",
                [
                    ':nome' => $obj->nome, ':telefone' => $obj->telefone, ':email' => $obj->email, ':datanascimento' => $obj->datanascimento,
                    ':nacionalidade' => $obj->nacionalidade, ':estadocivil' => $obj->estadocivil, ':segundoproponente' => $obj->segundoproponente,
                    ':tipohabitacao' => $obj->tipohabitacao, ':anoiniciohabitacao' => $obj->anoiniciohabitacao, ':valorhabitacao' => $obj->valorhabitacao,
                    ':tipohabitacao2' => $obj->tipohabitacao2, ':anoiniciohabitacao2' => $obj->anoiniciohabitacao2, ':valorhabitacao2' => $obj->valorhabitacao2,
                    ':tipocontrato' => $obj->tipocontrato, ':desde' => $obj->anoinicio, ':desdemes' => $obj->mesinicio, ':tipocontrato2' => $obj->tipocontrato2,
                    ':desde2' => $obj->anoinicio2, ':desdemes2' => $obj->mesinicio2,
                    ':lead' => $lead
                ]
            );
        } else {
            $this->insertToProcessForm($lead, $obj);
        }
    }

    /**
     * Register a new access for AC
     * needs to get lead by email (get last lead with this email)
     */
    public function registerClient($obj)
    {
        $result  = $this->db->query(
            "SELECT * FROM arq_leads WHERE email=:email ORDER BY dataentrada DESC LIMIT 1",
            [
                ':email' => $obj->emailRegister
            ]
        );
        if ($result) {
            $pass = passwordHash::hash($obj->password1);
            // Verificar no cad clientes se existe esta lead, se existir atualiza a senha
            try {
                $resp = $this->db->query(
                    "INSERT INTO cad_clientes(lead, nome, email, nif, password) "
                        . " VALUES(:lead, :nome, :email, :nif, :password)",
                    [
                        ':lead' => $result[0]['id'],
                        ':nome' => $result[0]['nome'],
                        ':email' => $result[0]['email'],
                        ':nif' => $result[0]['nif'],
                        ':password' => $pass
                    ]
                );
                // Atualizar o status da lead para iniciado (37) pela AC se o status for < 8
                if ($this->getStatus($result[0]['id']) < 8) {
                    $this->updateStatus($result[0]['id'], 37);
                }
                return $resp;
            } catch (\Throwable $exc) {
                try {
                    //code...
                    return $registed = $this->db->query(
                        "UPDATE cad_clientes SET password=:password WHERE lead=:lead ORDER BY id DESC LIMIT 1",
                        [
                            ':lead' => $result[0]['id'],
                            ':password' => $pass
                        ]
                    );
                } catch (\Throwable $th) {
                    return false;
                }
            }
        }
        return false;
    }

    public function recoverSenha($email)
    {
        $result = $this->db->query(
            "SELECT * FROM arq_leads WHERE email=:email ORDER BY id DESC LIMIT 1",
            [
                ':email' => $email
            ]
        );
        if ($result && $result[0]) {
            // criar novo acesso para esta lead - gerar senha e atualizar/inserir no cad_clientes
            $pass = gerarPassword(6);
            $obj = new stdClass();
            $obj->emailRegister = $email;
            $obj->password1 = $pass;
            $this->registerClient($obj);
            $assunto = "Novo acesso Gestlifes";
            $msg = "<p>Olá " . $result[0]['nome'] . ",</p><p>Para aceder à Área de cliente use a seguinte senha: <strong>" . $pass . "</strong></p>";
            return new sendEmail($email, $assunto, $msg, 'Pedido de nova senha', $result[0]['id']);
        } else {
            return null;
        }
    }


    /**
     * Returns 
     */
    private function generateToken($resp)
    {
        //Chave para a encriptação
        $key = 'klEp15FGcl2020';

        //obter o status da lead
        $sts = $this->db->query("SELECT status FROM arq_leads WHERE id=:lead", [':lead' => $resp['lead']])[0];

        //Configuração do JWT
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $header = json_encode($header);
        $header = base64_encode($header);

        //Obter o nome do fornecedor
        //Dados 
        $payload = [
            'iss' => 'GESTLIFES',
            'id' => $resp['id'],                // client id
            'lead' => $resp['lead']         // the last active lead for this client
        ];

        $payload = json_encode($payload);
        $payload = base64_encode($payload);

        //Signature

        $signature = hash_hmac('sha256', "$header.$payload", $key, true);
        $signature = base64_encode($signature);
        // echo $header.$payload.$signature;

        return "$header.$payload.$signature";
    }

    /**
     * Check if the token is valid
     * @param type $token
     * @return int 
     */
    private function checkToken($token)
    {
        return $this->db->query("SELECT count(*) FROM cad_clientes WHERE token=:token", [':token' => $token]);
    }




    private function checkFields($lead, $obj)
    {
        //Check arq_processo fields
        if ($this->getProcesso($lead)) {
            $old = $this->getProcesso($lead);
        }
        //get idade by datanascimento
        if (isset($obj->datanascimento)) {
            //explode the date to get month, day and year
            /*  $birthDate = explode("/", $obj->datanascimento); */
            $birthDate = array();
            $birthDate[0] = substr($obj->datanascimento, 0, 2);
            $birthDate[1] = substr($obj->datanascimento, 2, 2);
            $birthDate[2] = substr($obj->datanascimento, 4, 4);

            //get age from date or birthdate
            $obj->idade = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md")
                ? ((date("Y") - $birthDate[2]) - 1)
                : (date("Y") - $birthDate[2]));
        }
        if (isset($obj->datainicio)) {
            $obj->mesinicio = substr($obj->datainicio, 0, 2);
            $obj->anoinicio = substr($obj->datainicio, 2, 4);
        }
        if (isset($obj->datainicio2)) {
            $obj->mesinicio2 = substr($obj->datainicio2, 0, 2);
            $obj->anoinicio2 = substr($obj->datainicio2, 2, 4);
        }

        !isset($obj->nome) ? (isset($old['nome']) ? $obj->nome = $old['nome'] : $obj->nome = '')  : null;
        !isset($obj->nif) ? (isset($old['nif']) ? $obj->nif = $old['nif'] : $obj->nif = '')  : null;
        !isset($obj->email) ? (isset($old['email']) ? $obj->email = $old['email'] : $obj->email = '')  : null;
        !isset($obj->telefone) ? (isset($old['telefone']) ? $obj->telefone = $old['telefone'] : $obj->telefone = '')  : null;
        !isset($obj->idade) ? (isset($old['idade']) ? $obj->idade = $old['idade'] : $obj->idade = 0)  : null;
        !isset($obj->profissao) ? (isset($old['profissao']) ? $obj->profissao = $old['profissao'] : $obj->profissao = '')  : null;
        !isset($obj->montante) ? (isset($old['montante']) ? $obj->montante = $old['montante'] : $obj->montante = 0)  : null;
        !isset($obj->prazopretendido) ? (isset($old['prazopretendido']) ? $obj->prazopretendido = $old['prazopretendido'] : $obj->prazopretendido = 0)  : null;
        !isset($obj->vencimento) ? (isset($old['vencimento']) ? $obj->vencimento = $old['vencimento'] : $obj->vencimento = 0)  : null;
        !isset($obj->tipocontrato) ? (isset($old['tipocontrato']) ? $obj->tipocontrato = $old['tipocontrato'] : $obj->tipocontrato = 0)  : null;
        !isset($obj->mesinicio) ? (isset($old['mesinicio']) ? $obj->mesinicio = $old['mesinicio'] : $obj->mesinicio = 1)  : null;
        !isset($obj->anoinicio) ? (isset($old['anoinicio']) ? $obj->anoinicio = $old['anoinicio'] : $obj->anoinicio = 0)  : null;
        !isset($obj->estadocivil) ? (isset($old['estadocivil']) ? $obj->estadocivil = $old['estadocivil'] : $obj->estadocivil = 0)  : null;
        !isset($obj->tipohabitacao) ? (isset($old['tipohabitacao']) ? $obj->tipohabitacao = $old['tipohabitacao'] : $obj->tipohabitacao = 0)  : null;
        !isset($obj->anoiniciohabitacao) ? (isset($old['anoiniciohabitacao']) ? $obj->anoiniciohabitacao = $old['anoiniciohabitacao'] : $obj->anoiniciohabitacao = 0)  : null;
        !isset($obj->valorhabitacao) ? (isset($old['valorhabitacao']) ? $obj->valorhabitacao = $old['valorhabitacao'] : $obj->valorhabitacao = 0)  : null;
        !isset($obj->segundoproponente) ? (isset($old['segundoproponente']) ? $obj->segundoproponente = $old['segundoproponente'] : $obj->segundoproponente = 0)  : null;
        !isset($obj->relacaofamiliar) ? (isset($old['relacaofamiliar']) ? $obj->relacaofamiliar = $old['relacaofamiliar'] : $obj->relacaofamiliar = 0)  : null;
        !isset($obj->profissao2) ? (isset($old['profissao2']) ? $obj->profissao2 = $old['profissao2'] : $obj->profissao2 = '')  : null;
        !isset($obj->vencimento2) ? (isset($old['vencimento2']) ? $obj->vencimento2 = $old['vencimento2'] : $obj->vencimento2 = 0)  : null;
        !isset($obj->tipocontrato2) ? (isset($old['tipocontrato2']) ? $obj->tipocontrato2 = $old['tipocontrato2'] : $obj->tipocontrato2 = 0)  : null;
        !isset($obj->mesinicio2) ? (isset($old['mesinicio2']) ? $obj->mesinicio2 = $old['mesinicio2'] : $obj->mesinicio2 = 1)  : null;
        !isset($obj->anoinicio2) ? (isset($old['anoinicio2']) ? $obj->anoinicio2 = $old['anoinicio2'] : $obj->anoinicio2 = 0)  : null;
        !isset($obj->tipohabitacao2) ? (isset($old['tipohabitacao2']) ? $obj->tipohabitacao2 = $old['tipohabitacao2'] : $obj->tipohabitacao2 = 0)  : null;
        !isset($obj->anoiniciohabitacao2) ? (isset($old['anoiniciohabitacao2']) ? $obj->anoiniciohabitacao2 = $old['anoiniciohabitacao2'] : $obj->anoiniciohabitacao2 = 0)  : null;
        !isset($obj->valorhabitacao2) ? (isset($old['valorhabitacao2']) ? $obj->valorhabitacao2 = $old['valorhabitacao2'] : $obj->valorhabitacao2 = 0)  : null;
        !isset($obj->mesmahabitacao) ? (isset($old['mesmahabitacao']) ? $obj->mesmahabitacao = $old['mesmahabitacao'] : $obj->mesmahabitacao = 0)  : null;
        !isset($obj->tipocredito) ? (isset($old['tipocredito']) ? $obj->tipocredito = $old['tipocredito'] : $obj->tipocredito = 'CP')  : null;
        !isset($obj->finalidade) ? (isset($old['finalidade']) ? $obj->finalidade = $old['finalidade'] : $obj->finalidade = '')  : null;

        //Check arq_processo_form fields
        if ($this->getProcessoForm($lead)) {
            $old = $this->getProcessoForm($lead);
        }
        !isset($obj->datanascimento) ? (isset($old['datanascimento']) ? $obj->datanascimento = $old['datanascimento'] : $obj->datanascimento = '')  : null;
        !isset($obj->nacionalidade) ? (isset($old['nacionalidade']) ? $obj->nacionalidade = $old['nacionalidade'] : $obj->nacionalidade = 1)  : null;

        return $obj;
    }


    private function getProcesso($lead)
    {
        $result = $this->Lead->getProcesso($lead);
        if (sizeof($result) > 0) {
            return $result[0];
        } else {
            return false;
        }
    }

    private function getProcessoForm($lead)
    {
        $result = $this->Lead->getProcessoForm($lead);
        if (sizeof($result) > 0) {
            return $result[0];
        } else {
            return false;
        }
    }
}
