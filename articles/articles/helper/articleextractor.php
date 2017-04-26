<?php
/**
 * User: jacopostrada
 * Date: 21/04/17
 * Time: 19:59
 *
 *Funzioni per formattare il testo di una mail in modo da adattarlo al formato
 *di un articolo Joomla
 */
defined('_JEXEC') or die('Restricted access');

const emailSignature = "Note in ottemperanza al Decreto Legislativo 196/2003";
const articleOrizontalWidth = "570px";

/**
*Funzione che ritorna la classe di icone font awesome per la lista di allegati
*@param $fileExt: estensione del file d'allegato
*/
function getFileIcon($fileExt){
  switch($fileExt) {
    case "pdf":
      return 'fa-file-pdf-o';
    case "xls":
    case "xlsx":
      return 'fa-file-excel-o';
    case "doc":
    case "docx":
      return 'fa-file-word-o';
    case "png":
    case "jpg":
    case "jpeg":
      return 'fa-file-image-o';
    case "ppt":
    case "pptx":
      return 'fa-file-powerpoint-o';
    case "rar":
    case "zip":
    case "tar":
      return 'fa-file-archive-o';
    default:
      return 'fa-file-o';
  }
}

/**
*funzione per generare l'elemento della ul contentente gli allegati
*@param $dom: domdocument ricevuto per email
*@param $attachmentName: nome dell'allegato
*@param $attachmentName: link di Google Drive relativo all'allegato
*@param $fileExt: estensione del file d'allegato
*/
function getLiElement($dom, $attachmentName, $attachmentLink, $fileExt){
  $liElement = $dom->createElement('li');
  $liElement->setAttribute('style',"list-style-type: none;");
  $iElement = $dom->createElement('i');
  $iElement->setAttribute('class', 'fa ' . getFileIcon($fileExt));
  $liElement->appendChild($iElement);
  $aElement = $dom->createElement('a', ' ' . $attachmentName);
  $aElement->setAttribute('href', $attachmentLink);
  $iElement->appendChild($aElement);
  return $liElement;
}

/**
*Funzione per aggiungere al corpo dell'articolo la lista degli allegati e inserire,
*se presente, la prima immagine allegata come immagine principale dell'articolo
*@param $dom: domdocument ricevuto per email
*@param $attachmentsJson: Json, generato dallo script su Google Drive, nel
*                         formato {nomeAllegato1:linkAllegato1, etc etc...}
*/
function addAttacchmentsListToHtml(DOMDocument $dom, $attachmentsJson){
  $xpath = new DOMXPath($dom);
  $parent = $xpath->query("//body");
  $child = $xpath->query("//body/*[1]");
  $body = $dom->getElementsByTagName('body')[0];
  $ulList = $dom->createElement('ul');
  $firstImage = true;
  foreach ($attachmentsJson as $attachmentName => $attachmentLink) {
    $fileExt = substr($attachmentName, strrpos($attachmentName, '.')+1);
    if(($fileExt=="png" || $fileExt=="jpeg" || $fileExt=="jpg") && $firstImage===true){
      $imgElement = $dom->createElement('img');
      $imgElement->setAttribute('src', $attachmentLink);
      $imgElement->setAttribute('style','display: block; width:100%;margin: 10px auto; max-width:'.articleOrizontalWidth);
      $parent->item(0)->insertBefore($imgElement, $child->item(0));
      $firstImage = false;
    }
    $ulList->appendChild(getLiElement($dom, $attachmentName, $attachmentLink, $fileExt));
  }
  $body->appendChild($ulList);
}

/**
*funzione per rimuovere la firma contenuta a fondo pagina nella mail
*@param $domNode: domNode root dell'html ricevuto per email: la funzione è
*                 ricorsiva, quindi al primo passaggio di ricorsione il parametro
*                 sarà il domdocument ricevuto per email, ma ad ogni passaggio
*                 successivo della ricorsione sarà un nodo figlio del domdocument
*/
function removeSignature(DOMNode $domNode)
{
    $elements = $domNode->childNodes;
    for ($i = $elements->length; --$i >= 0; ) {
        if ($elements->item($i)->tagName == 'p'){
          $text = $elements->item($i)->textContent;
          $separatorPos = strrpos($text, "--");
          if($separatorPos!==false){
            $elements->item($i)->textContent = substr($text, 0, $separatorPos);
          }
        }
            //ogni nodo viene passato per accertarsi di togliere la signature
        if ($elements->item($i)->hasChildNodes()) {
          removeSignature($elements->item($i));
        }
        if (strpos($elements->item($i)->textContent, emailSignature) !== false) {
          $elements->item($i)->parentNode->parentNode->removeChild($elements->item($i)->parentNode);
        }
    }
}

/**
*spesso le mail hanno dei tag img che però sono relativi ad immagini non
*raggiungibili al di fuori della mail stessa in quanto allegati alla mail:
*gli allegati sono quindi sostituiti con delle loro copie salvate in drive.
*Questa funzione rimuove i tag img contenuti nella mail originale
*@param $domNode: nodo principale del documento html ricevuto
*/
function removeImg(DOMNode $domNode){
  $imgElements = $domNode->getElementsByTagName('img');
  for ($i = $imgElements->length; --$i >= 0; ) {
    $href = $imgElements->item($i);
    $href->parentNode->removeChild($href);
  }
}

/**
*Le tabelle ricevute per email sono spesso troppo larghe per essere contenute
*nello spazio di un articolo: con questa funzione viene aggiunto, come loro
*elemento padre, un div con stile "overflow-x: auto", che permette alla tabella
*di essere scrollabile in orizzontale senza sforare dallo spazio dell'articolo
*@param $dom: nodo principale dell'html
*/
function fixTables($dom){
  $tableElements = $dom->getElementsByTagName('table');
  foreach ($tableElements as $element) {
    $tableContainer = $dom->createElement('div');
    $tableContainer->setAttribute("style", "overflow-x: auto;");
    $element->parentNode->insertBefore($tableContainer, $element);
    $tableContainer->appendChild($element);
  }
}

/**
*funzione che chiama i metodi per adattare l'html o plain-text ricevuto alla
*pubblicazione in un articolo Joomla
*@param $mailBody: html o plain-text della mail ricevuta
*@param $attachmentsJson: Json, generato dallo script su Google Drive, nel
*                         formato {nomeAllegato1:linkAllegato1, etc etc...}
*/
function formatArticle($mailBody, $attachmentsJsonString)
{
    $DOM = new DOMDocument();
    $DOM->loadHTML($mailBody);
    $attachmentsJson = json_decode($attachmentsJsonString, true);
    removeImg($DOM);
    removeSignature($DOM);
    fixTables($DOM);
    addAttacchmentsListToHtml($DOM, $attachmentsJson);

    return $DOM->saveHTML($DOM);
}




// $emailBody = '<html><head><meta http-equiv="Content-Type" content="text/html charset=utf-8"></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space;" class=""><p style="font-family: Verdana, Geneva, sans-serif; font-size: 13.333333015441895px;" class="">Ciao a tutti di seguito il calendario per il servizio provette, date un occhio in modo tale che se avete delle modifiche da fare le mettiamo prima di appendere la turnazione definitiva</p><p style="font-family: Verdana, Geneva, sans-serif; font-size: 13.333333015441895px;" class=""><br class=""></p><p style="font-family: Verdana, Geneva, sans-serif; font-size: 13.333333015441895px;" class="">Grazie mille</p><p style="font-family: Verdana, Geneva, sans-serif; font-size: 13.333333015441895px;" class=""><br class=""></p><p style="font-family: Verdana, Geneva, sans-serif; font-size: 13.333333015441895px;" class=""><br class=""></p><table width="927" style="font-family: Verdana, Geneva, sans-serif;" class=""><tbody class=""><tr class=""><td width="86" class="">Cognome</td><td width="116" class="">Nome</td><td width="218" class="">Cellulare</td><td width="229" class="">Turno Nome</td><td width="138" class="">Turno Inizio</td><td width="140" class="">Turno Fine</td></tr><tr class=""><td class="">Redaelli</td><td class="">Francesca</td><td class="">348 581 5620</td><td class="">Trasporto provette su chiamata</td><td class="">06/05/2017 10.00</td><td class="">06/05/2017 11.30</td></tr><tr class=""><td class="">Bonsanto</td><td class="">Fabio</td><td class="">333 398 7235</td><td class="">Trasporto provette su chiamata</td><td class="">13/05/2017 10.00</td><td class="">13/05/2017 11.30</td></tr><tr class=""><td class="">Rosano</td><td class="">Nicola</td><td class="">339 219 5310</td><td class="">Trasporto provette su chiamata</td><td class="">20/05/2017 10.00</td><td class="">20/05/2017 11.30</td></tr><tr class=""><td class="">Pozzi</td><td class="">Enrico Giovanni</td><td class="">333 252 1245</td><td class="">Trasporto provette su chiamata</td><td class="">27/05/2017 10.00</td><td class="">27/05/2017 11.30</td></tr><tr class=""><td class="">Mantonico</td><td class="">Alfredo</td><td class="">347 830 3201</td><td class="">Trasporto provette su chiamata</td><td class="">03/06/2017 10.00</td><td class="">03/06/2017 11.30</td></tr><tr class=""><td class="">Ghirini</td><td class="">Arnaldo</td><td class="">338 262 3987</td><td class="">Trasporto provette su chiamata</td><td class="">10/06/2017 10.00</td><td class="">10/06/2017 11.30</td></tr><tr class=""><td class="">Carnio</td><td class="">Maristella</td><td class="">349 428 0592</td><td class="">Trasporto provette su chiamata</td><td class="">17/06/2017 10.00</td><td class="">17/06/2017 11.30</td></tr><tr class=""><td class="">Bello</td><td class="">Marco</td><td class="">333 273 9878 – 039 571 39</td><td class="">Trasporto provette su chiamata</td><td class="">24/06/2017 10.00</td><td class="">24/06/2017 11.30</td></tr><tr class=""><td class="">Valnegri</td><td class="">Andrea</td><td class="">333 398 7625</td><td class="">Trasporto provette su chiamata</td><td class="">01/07/2017 10.00</td><td class="">01/07/2017 11.30</td></tr><tr class=""><td class="">Rocca</td><td class="">Valeria</td><td class="">340 577 6832</td><td class="">Trasporto provette su chiamata</td><td class="">08/07/2017 10.00</td><td class="">08/07/2017 11.30</td></tr><tr class=""><td class="">Brenna</td><td class="">Debora</td><td class="">333 945 0394</td><td class="">Trasporto provette su chiamata</td><td class="">15/07/2017 10.00</td><td class="">15/07/2017 11.30</td></tr><tr class=""><td class="">Casiraghi</td><td class="">Michele</td><td class="">339 806 5984</td><td class="">Trasporto provette su chiamata</td><td class="">22/07/2017 10.00</td><td class="">22/07/2017 11.30</td></tr><tr class=""><td class="">Bellantonio</td><td class="">Giuseppe</td><td class="">349 372 8598</td><td class="">Trasporto provette su chiamata</td><td class="">29/07/2017 10.00</td><td class="">29/07/2017 11.30</td></tr><tr class=""><td class="">Bonfanti</td><td class="">Eros</td><td class="">348515 8644</td><td class="">Trasporto provette su chiamata</td><td class="">05/08/2017 10.00</td><td class="">05/08/2017 11.30</td></tr><tr class=""><td class="">Cazzaniga</td><td class="">Matteo</td><td class="">338 615 8657</td><td class="">Trasporto provette su chiamata</td><td class="">12/08/2017 10.00</td><td class="">12/08/2017 11.30</td></tr><tr class=""><td class="">Ielpo</td><td class="">Antonio</td><td class="">339 324 4769</td><td class="">Trasporto provette su chiamata</td><td class="">19/08/2017 10.00</td><td class="">19/08/2017 11.30</td></tr><tr class=""><td class="">Villa</td><td class="">Daniela</td><td class="">333 441 3314</td><td class="">Trasporto provette su chiamata</td><td class="">26/08/2017 10.00</td><td class="">26/08/2017 11.30</td></tr></tbody></table><p style="font-family: Verdana, Geneva, sans-serif; font-size: 13.333333015441895px;" class=""><br class=""></p><p style="font-family: Verdana, Geneva, sans-serif; font-size: 13.333333015441895px;" class=""><br class=""></p><div style="font-family: Verdana, Geneva, sans-serif; font-size: 13.333333015441895px;" class="">--&nbsp;<br class=""><div class="pre" style="margin: 0px; padding: 0px; font-family: monospace;">Turnazione - CRI Casatenovo<br class=""><a href="http://www.cricasatenovo.it" target="_blank" rel="noreferrer" class="">www.cricasatenovo.it</a><br class=""><br class="">Facebook&nbsp;<a href="http://www.facebook.com/cricasatenovo" target="_blank" rel="noreferrer" class="">www.facebook.com/cricasatenovo</a><br class="">Twitter&nbsp;<a href="http://www.twitter.com/@cricasatenovo" target="_blank" rel="noreferrer" class="">www.twitter.com/@cricasatenovo</a><br class="">Youtube&nbsp;<a href="http://www.youtube.com/cricasate" target="_blank" rel="noreferrer" class="">www.youtube.com/cricasate</a><br class=""><br class="">Note in ottemperanza al Decreto Legislativo 196/2003 sulla Tutela dei Dati Personali: il presente messaggio è rivolto unicamente all\'attenzione del destinatario ed il relativo contenuto potrebbe avere carattere riservato; ne è vietata la diffusione in qualunque modo eseguita. Nel caso in cui aveste ricevuto questa mail per errore, Vi invitiamo ad avvertire il mittente al più presto a mezzo posta elettronica e a distruggere il messaggio erroneamente ricevuto.</div></div><table></table></body></html>';
//
// $j = '{"nome": "link","nome2.png":"link2"}';
// echo formatArticle($emailBody, $j);
//$Dom = new DOMDocument();
//$Dom->loadHTML($emailBody);
//echo $emailBody;
//echo formatArticle($emailBody);

// $json = '{"link1":"nome1","link2":"nome2"}';
// $arr = json_decode($json,true);
// foreach ($arr as $key => $value) {
//   echo $key . ':' . $value . '\n';
// }
