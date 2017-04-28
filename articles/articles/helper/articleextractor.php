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





 $emailBody = '<html> <head> <meta http-equiv="content-type" content="text/html; charset=iso-8859-15"> </head> <body bgcolor="#FFFFFF" text="#000000"> <p>Tutti i volontari riceveranno 1000� al mese a partire da Aprile.<br> Come pu� confermare l\'admin del sito:<br> <img src="cid:part1.1A92D8FC.7DA4FB5B@cricasatenovo.it" alt=""><br> </p> <pre class="moz-signature" cols="72">-- WebMaster - CRI Casatenovo <a class="moz-txt-link-abbreviated" href="http://www.cricasatenovo.it">www.cricasatenovo.it</a> Facebook <a class="moz-txt-link-abbreviated" href="http://www.facebook.com/cricasatenovo">www.facebook.com/cricasatenovo</a> Twitter <a class="moz-txt-link-abbreviated" href="http://www.twitter.com/@cricasatenovo">www.twitter.com/@cricasatenovo</a> Youtube <a class="moz-txt-link-abbreviated" href="http://www.youtube.com/cricasate">www.youtube.com/cricasate</a> Note in ottemperanza al Decreto Legislativo 196/2003 sulla Tutela dei Dati Personali: il presente messaggio e gli eventuali allegati sono rivolti unicamente all\'attenzione del destinatario ed il relativo contenuto potrebbe avere carattere riservato e ne � vietata la diffusione in qualunque modo eseguita. Nel caso in cui aveste ricevuto questa mail per errore, Vi invitiamo ad avvertire il mittente al pi� presto a mezzo posta elettronica e a distruggere il messaggio erroneamente ricevuto.</pre> </body> </html>';
//
$j = '{"nome": "link","nome2.png":"link2"}';
echo formatArticle($emailBody, $j);
//$Dom = new DOMDocument();
//$Dom->loadHTML($emailBody);
//echo $emailBody;
//echo formatArticle($emailBody);

// $json = '{"link1":"nome1","link2":"nome2"}';
// $arr = json_decode($json,true);
// foreach ($arr as $key => $value) {
//   echo $key . ':' . $value . '\n';
// }
