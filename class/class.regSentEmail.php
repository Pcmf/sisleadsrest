<?php
/**
 * Description of regSentEmail
 *
 * @author pedro
 */
class regSentEmail {
    private $user;
    private $destino;
    private $assunto;
    private $db;
    //put your code here
    public function __construct($u,$d,$a) {
        $this->user = $u;
        $this->destino = $d;
        $this->assunto = $a;
        $this->db = new DB();
      
    }
    
    function registOk() {
        //insere como com sucesso
        $this->db->query("INSERT INTO arq_logemail(user, destino, assunto) VALUES(:user, :destino, :assunto)",
                [
                    ':user'=>$this->user,
                    ':destino'=>$this->destino,
                    ':assunto'=>$this->assunto
                ]);
    }
    function registErro($erro) {
        //insere como com sucesso
        $this->db->query("INSERT INTO arq_logemail(user,destino,assunto,erro) VALUES( :user, :destino, :assunto, :erro)",
        [
            ':user'=>$this->user,
            ':destino'=>$this->destino,
            ':assunto'=>$this->assunto,
            ':erro'=>$erro
        ]);
    }
}
