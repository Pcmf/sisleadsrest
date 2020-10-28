<?php

/**
 * Description of Lead
 * 
 *  This class provides all the information about the lead and process
 *  Provides also methods to insert, update data on it.
 *
 * @author pedro
 */
class TLead {
    private $db;
    
    public function __construct() {
        $this->db = new DB();
    }

    
    /**
     * Get all arq_lead fields
     * @param type $lead
     * @return type
     */
    protected function getLead($lead) {
        return $this->db->query("SELECT * FROM arq_lead WHERE id=:lead ", [':lead'=>$lead]);
    }
    
    protected function insertLead($obj) {
        
    }
    
    
    protected function update($lead, $obj) {
        
    }
    
    
    private function checkFields($lead, $obj) {
                //Check arq_lead fields
        if ($this->getLead($lead)){
            $old = $this->getLead($lead);
        }
        !isset($obj->nome) ? (isset($old->nome) ? $obj->nome = $old->nome : $obj->nome = '')  : null;
        !isset($obj->nif) ? (isset($old->nif) ? $obj->nif = $old->nif : $obj->nif = '')  : null;
        !isset($obj->email) ? (isset($old->email) ? $obj->email = $old->email : $obj->email = '')  : null;
        !isset($obj->telefone) ? (isset($old->telefone) ? $obj->telefone = $old->telefone : $obj->telefone = '')  : null;
        !isset($obj->tipo) ? (isset($old->tipo) ? $obj->tipo = $old->tipo : $obj->tipo = '')  : null;
        !isset($obj->idade) ? (isset($old->idade) ? $obj->idade = $old->idade : $obj->idade = '')  : null;
        !isset($obj->montante) ? (isset($old->montante) ? $obj->montante = $old->montante : $obj->montante = '')  : null;
        !isset($obj->prazopretendido) ? (isset($old->prazopretendido) ? $obj->prazopretendido = $old->prazopretendido : $obj->prazopretendido = '')  : null;
        !isset($obj->rendimento1) ? (isset($old->rendimento1) ? $obj->rendimento1 = $old->rendimento1 : $obj->rendimento1 = '')  : null;
        !isset($obj->tipocontrato) ? (isset($old->tipocontrato) ? $obj->tipocontrato = $old->tipocontrato : $obj->tipocontrato = '')  : null;
        !isset($obj->rendimento2) ? (isset($old->rendimento2) ? $obj->rendimento2 = $old->rendimento2 : $obj->rendimento2 = '')  : null;
        !isset($obj->status) ? (isset($old->status) ? $obj->status = $old->status : $obj->status = '')  : null;
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
    
}
