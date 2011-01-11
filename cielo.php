<?php
/**
 * Cielo CakePHP Component
 * Copyright (c) 2011 Luan Garcia
 * @link www.implementado.com
 * @author      Luan Garcia <luan.garcia@gmail.com>
 * @version     1.0
 * @license     MIT
 *
 */

class CieloComponent extends Object {

    public $teste       = false;    //por padrao é false.
    public $shopid      = null;     //Número de afiliação da loja com a Cielo..
    public $chave       = null;     //Chave de acesso da loja atribuída pela Cielo.
    public $loja_nome   = null;     //Nome da Loja
    public $pedido      = null;     //Id do pedido na loja
    public $bandeira    = null;     //visa ou master
    public $parcelas    = null;     //verificar na cielo a quantidade liberada para cada bandeira
    public $valor       = null;     //valor da compra
    public $capturar    = false;    //[true|false]. Define se a transação será automaticamente capturada caso seja autorizada.
    public $autorizar   = 2;        //Indicador de autorização automática: 0 (não autorizar) 1 (autorizar somente se autenticada) 2 (autorizar autenticada e não-autenticada) 3 (autorizar sem passar por autenticação – válido somente para crédito)
    public $url_teste   = 'https://qasecommerce.cielo.com.br/servicos/ecommwsec.do';
    public $url         = 'https://ecommerce.cbmp.com.br/servicos/ecommwsec.do';
    public $url_retorno = null;     //Url de retorno apos informar os dados do cartão na no by page cielo

    /**
     * Metodo realiza a criacao do pedido junto a visa
     * Return mixed dados do pedido na visa junto com TID do pedido,url-autenticacao (tela finalizacao da compra)
     */
    function criar_pedido() {

        /**
         * Se as parcelas forem > 1 produto=Crédito à Vista senão produto=Parcelado loja
         */
        $produto = $this->parcelas > 1 ? 2 : 1;
        /**
         * Data hora do pedido 
         */
        $data = date("Y-m-d\TH:i:s");
        /**
         * Limpa o valor para a visa
         */
        $valor = preg_replace('/[^0-9]+/', "", number_format($this->valor, 2, ",", "."));
        $post = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
                <requisicao-transacao id=\"1\" versao=\"1.1.0\" xmlns=\"http://ecommerce.cbmp.com.br\">
                    <dados-ec>
                        <numero>{$this->shopid}</numero>
                        <chave>{$this->chave}</chave>
                        <nome>{$this->loja_nome}</nome>
                        <codigo-pais>097</codigo-pais>
                    </dados-ec>
                    <dados-pedido>
                        <numero>{$this->pedido}</numero>
                        <valor>{$valor}</valor>
                        <moeda>986</moeda>
                        <data-hora>{$data}</data-hora>
                    </dados-pedido>
                    <forma-pagamento>
                        <bandeira>{$this->bandeira}</bandeira>
                        <produto>{$produto}</produto>
                        <parcelas>{$this->parcelas}</parcelas>
                    </forma-pagamento>
                    <url-retorno>{$this->url_retorno}</url-retorno>
                    <autorizar>{$this->autorizar}</autorizar>
                    <capturar>{$this->capturar}</capturar>
                </requisicao-transacao>";
        App::import("Xml");
        $retorno = Set::reverse(new Xml($this->file_post_contents($post)));
         
        #Log para debug futuro em produção, facilita o debug no cliente
        
        if(isset($retorno['Erro'])) {
            $log =  var_export($retorno, true);
            $this->log('ERRO - AO CRIAR TID\r\n'.$log.'\r\n', LOG_DEBUG);
        }else {
            $log =  var_export($retorno, true);
            $this->log('SUCESSO - AO CRIAR TID\r\n'.$log.'\r\n', LOG_DEBUG);
        }
        return $retorno;
    }

    /**
     * Metodo realiza a consulta do pedido na visa
     * @param @tid do pedido
     * @return mixed
     */
    function consultar_pedido($tid) {
        $post = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <requisicao-consulta id=\"5\" versao=\"1.0.0\" xmlns=\"http://ecommerce.cbmp.com.br\">
                        <tid>{$tid}</tid>
                        <dados-ec>
                            <numero>{$this->shopid}</numero>
                            <chave>{$this->chave}</chave>
                        </dados-ec>
                    </requisicao-consulta>";
        App::import("Xml");
        $retorno_visa = Set::reverse(new Xml($this->file_post_contents($post)));
        
        return $retorno_visa;
    }

    /**
     * Metodo realiza o post do xml local para a visa
     * @param String Xml com os dados do pedido
     * @return mixed
     */
    function file_post_contents($msg) {
        $postdata = http_build_query(array('mensagem' => $msg));

        $opts = array('http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);

        if ($this->teste === true) {
            $url = $this->url_teste;
        }else{
            $url = $this->url;
        }
        return file_get_contents($url, false, $context);
    }
}
?>