<?php

/**
 * Description of Lead
 * 
 *  This class provides all the information about the lead and process
 *  Provides also methods to insert, update data on it.
 *
 * @author pedro
 */
class Lead {
    private $db;
    
    public function __construct() {
        $this->db = new DB();
    }
    
    /**
     * Get information needed to AC
     * @param type $lead
     * @return type array
     */
    public function getProcessInfo($lead) {
        return $this->db->query("SELECT L.id, L.status, L.datastatus, L.dataentrada, L.user AS gestor, "
                . " P.valorpretendido, P.tipocredito, P.prazopretendido, P.finalidade,"
                . " U.nome AS gestornome, U.email AS gestoremail, U.telefone AS gestortelefone, U.avatar, "
                . " S.nome AS statusnome "
                . " FROM arq_leads L "
                . " INNER JOIN arq_processo P ON P.lead=L.id "
                . " LEFT JOIN cad_utilizadores U ON U.id=L.user "
                . " INNER JOIN cnf_statuslead S ON S.id=L.status "
                . " WHERE L.id=:lead",
                [':lead'=>$lead]);
    }
    
    /**
     * Get all arq_lead fields
     * @param type $lead
     * @return type
     */
    public function getLead($lead) {
        return $this->db->query("SELECT * FROM arq_leads WHERE id=:lead ", [':lead'=>$lead]);
    }

    /**
     */
    public function getTitulares($lead) {
        return $this->db->query("SELECT segundoproponente FROM arq_processo WHERE lead=:lead ", [':lead'=>$lead]);
    }
        /**
     * Get all arq_processo fields
     * @param type $lead
     * @return type
     */
    public function getProcesso($lead) {
        return $this->db->query("SELECT * FROM arq_processo WHERE lead=:lead ", [':lead'=>$lead]);
    }
    
     /**
     * Get all arq_processo fields
     * @param type $lead
     * @return type
     */
    public function getProcessoForm($lead) {
        return $this->db->query("SELECT * FROM arq_process_form WHERE lead=:lead ", [':lead'=>$lead]);
    }

    public function getGestor($lead) {
        $result = $this->db->query("SELECT user FROM arq_leads WHERE id=:lead ", [':lead'=>$lead]);
        if ($result) {
            return $result[0]['user'];
        } else {
            return null;
        }
    }

    /**
     * Atribuir aleatoriamente um gestor ativo
     * 
     */
    public function setGestorRandom($lead) {
        //get list of active gestores
        
        $this->db->query("UPDATE arq_leads SET user=:user WHERE id=:lead", 
        [
            ':lead'=>$lead,
            ':user'=>$this->getRandomGestor
        ]);
    }

    public function getRandomGestor() {
        $gestList = $this->db->query("SELECT id FROM cad_utilizadores WHERE tipo='Gestor' AND ativo=1 AND presenca=1");
        return $gestList[random_int(0, sizeof($gestList)-1)]['id'];    
    }
}
