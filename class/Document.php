<?php
require_once __DIR__ . './../vendor/autoload.php';
/**
 * Description of Document
 *  This class is used to get information about documentation and insert, update or delete docs
 * @author pedro
 */




class Document
{
    private $db;
    private $Client;
    private $Lead;

    public function __construct()
    {
        $this->db = new DB();
        $this->Client = new Client();
        $this->Lead = new Lead();
    }

    public function getBasicDocs($lead)
    {
        // verificar se a lead tem segundo titular
        $resp = $this->Lead->getTitulares($lead);
        if ($resp[0]['segundoproponente']) {
            return $this->db->query("SELECT * FROM cnf_docnecessaria WHERE base IN(1,2) ORDER BY titular, ordem");
        } else {
            return $this->db->query("SELECT * FROM cnf_docnecessaria WHERE base=1 ORDER BY titular, ordem");
        }

        
    }


    public function getAskedDocs($lead)
    {   
        return $this->db->query(
            "SELECT D.*, T.*, DD.tipo, DD.nomefx "
                . " FROM cad_docpedida D "
                . " INNER JOIN cnf_docnecessaria T ON T.id=D.tipodoc "
                . " LEFT JOIN arq_documentacao DD ON DD.lead=D.lead AND DD.linha=D.linha "
                . " WHERE D.lead=:lead",
            [':lead' => $lead]
        );
    }


    public function getDoc($lead, $linha)
    {
        return $this->db->query(
            "SELECT D.*, DP.*, T.* "
                . " FROM arq_documentacao D "
                . " INNER JOIN cad_docpedida DP ON DP.lead=D.lead AND DP.linha=D.linha "
                . " INNER JOIN cnf_docnecessaria T ON T.id=DP.tipodoc "
                . " WHERE D.lead=:lead AND D.linha=:linha",
            [':lead' => $lead, ':linha' => $linha]
        );
    }

    // criar lista de documentação basica necessária
    public function createBasicNeededDocList($lead)
    {
        $list = $this->getBasicDocs($lead);
        $linha = 1;
        foreach ($list as $el) {
            $this->db->query(
                "INSERT INTO cad_docpedida(lead, linha, tipodoc) VALUES(:lead, :linha, :tipodoc)",
                [
                    ':lead' => $lead, ':linha' => $linha, ':tipodoc' => $el['id']
                ]
            );
            $linha++;
        }
    }

    public function saveDoc($lead, $obj)
    {
        // Guarda e se necessário converter a documentação para PDF
        if ($obj->type == 'pdf') {
            $fx64 = substr($obj->fx64, 28);
        } else {
            //Call function to convert to pdf. Returns base64 pdf
            try {
                $fx64 = $this->convToPdf($obj->fx64);
                $fx64 = preg_replace("/[\n\r]/", "", $fx64);
            } catch (Exception $exc) {
                echo $exc->getTraceAsString();
            }
            //alterar o nome do fx
            $obj->nomefx = substr($obj->nomefx, 0, strpos($obj->nomefx, '.')) . '.pdf';
        }

        $this->db->query(
            "INSERT INTO arq_documentacao(lead, linha, tipo, nomefx, fx64) VALUES(:lead, :linha, 'pdf', :nomefx, :fx64)",
            [
                ':lead' => $lead, ':linha' => $obj->linha, ':nomefx' => $obj->nomefx, ':fx64' => $fx64
            ]
        );
        // ATUALIZAR o cad_docpedida
        $this->db->query(
            "UPDATE cad_docpedida SET recebido=1, datarecebido=NOW(), notok= null, problem= 'Recebido. Aguarda aprovação!', dataproblem= NOW(), aproved=null, dataaproved=null "
                . " WHERE lead=:lead AND linha=:linha ",
            [
                ':lead' => $lead, ':linha' => $obj->linha
            ]
        );
        //Se a lead já estiver enviada para a analise não altera status
        if (!($this->Lead->getLeadStatus($lead) > 10 && $this->Lead->getLeadStatus($lead) < 30)) {
            // Verificar a situação da documentação pedida para atualizar os status
            if ($this->checkIsAllReceived($lead)) {
                // Atualiza o status da lead como documentação toda recebida
                $this->Client->updateStatus($lead, 36);
            } else {
                // aguarda documentação
                $this->Client->updateStatus($lead, 38);
            }
        }
    }

    public function checkIsAllReceived($lead)
    {
        $result = $this->db->query(
            "SELECT count(*) as count FROM cad_docpedida WHERE recebido=0 AND lead=:lead",
            [
                ':lead' => $lead
            ]
        );
        if ($result[0]['count'] > 0) {
            return false;
        } else {
            return true;
        }
    }


    public function deleteReceivedDoc($lead, $linha)
    {
        // remover doc recebido do arq_documentação
        $this->db->query(
            "DELETE FROM arq_documentacao WHERE lead=:lead AND linha=:linha ",
            [
                ':lead' => $lead, ':linha' => $linha
            ]
        );
        // atualizar o cad_docpedida para não recebido
        $this->db->query(
            "UPDATE cad_docpedida SET recebido=0, datarecebido=null, notok=null,  problem= null, dataproblem= null, aproved=null, dataaproved=null "
                . " WHERE lead=:lead AND linha=:linha ",
            [
                ':lead' => $lead, ':linha' => $linha
            ]
        );

        // Atualiza o status da lead como aguarda documentação
        $this->Client->updateStatus($lead, 38);
    }


    public function convToPdf($fx)
    {  //return base64 pdf
        $stamp = time();
        $filename = 'temp_' . $stamp;
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML('<img src="' . $fx . '" style="width: 210mm; height: 297mm; margin: 0;" />');
        $mpdf->showImageErrors = true;

        $mpdf->Output($filename, \Mpdf\Output\Destination::FILE);
        $b64Doc = chunk_split(base64_encode(file_get_contents($filename)));
        //remover o fx
        unlink($filename);
        return $b64Doc;
    }


    /**
     * Speedup 
     */
    public function speedUp($lead, $obj)
    {

        try {
            $this->db->query(
                "INSERT INTO cad_speedup(lead, nif, senha) VALUES(:lead, :nif, :senha)",
                [
                    ':nif' => $obj->nif,
                    ':senha' => $obj->senha,
                    ':lead' => $lead
                ]
            );
        } catch (Exception $e) {
            $this->db->query(
                "UPDATE cad_speedup SET nif=:nif, senha=:senha, visto=0, datavisto=null WHERE lead=:lead",
                [
                    ':nif' => $obj->nif,
                    ':senha' => $obj->senha,
                    ':lead' => $lead
                ]
            );
        } catch (\Throwable $th2) {
            return $th2;
        }
        
        // Verificar se a lead já tem utilizador definido
        if (!$this->Lead->getLead($lead)[0]['user']) {
          /*   echo $this->Lead->getLead($lead)[0]['user']; */
            $this->Lead->setGestorRandom($lead);
        }
        return;
    }
}
