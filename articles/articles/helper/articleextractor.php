<?php
/**
 * User: jacopostrada
 * Date: 21/04/17
 * Time: 19:59
 */
//defined('_JEXEC') or die('Restricted access');

const emailSignature = "Note in ottemperanza al Decreto Legislativo 196/2003";
const articleOrizontalWidth = "570px";


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

function fixElements(DOMNode $domNode)
{
    $elements = $domNode->childNodes;
    for ($i = $elements->length; --$i >= 0; ) {
        if ($elements->item($i)->tagName == 'table'){
          $tableContainer = $domNode->ownerDocument->createElement('div');
          $tableContainer->setAttribute("style", "overflow-x: auto;");
          $elements->item($i)->parentNode->insertBefore($tableContainer, $elements->item($i));
          $tableContainer->appendChild($elements->item($i));
        }
        if ($elements->item($i)->tagName == 'p'){
          $text = $elements->item($i)->textContent;
          $separatorPos = strrpos($text, "--");
          if($separatorPos!==false){
            $elements->item($i)->textContent = substr($text, 0, $separatorPos);
          }
        }
            //ogni nodo viene passato per accertarsi di togliere la signature
        if ($elements->item($i)->hasChildNodes()) {
          fixElements($elements->item($i));
        }
        if (strpos($elements->item($i)->textContent, emailSignature) !== false) {
          $elements->item($i)->parentNode->parentNode->removeChild($elements->item($i)->parentNode);
        }
    }
}

function removeImg(DOMNode $domNode){
  $imgElements = $domNode->getElementsByTagName('img');
  for ($i = $imgElements->length; --$i >= 0; ) {
    $href = $imgElements->item($i);
    $href->parentNode->removeChild($href);
  }
}

function formatArticle($mailBody, $attachmentsJsonString)
{
    $DOM = new DOMDocument();
    $DOM->loadHTML($mailBody);
    $attachmentsJson = json_decode($attachmentsJsonString, true);
    removeImg($DOM);
    fixElements($DOM);
    addAttacchmentsListToHtml($DOM, $attachmentsJson);

    return $DOM->saveHTML($DOM);
}




$emailBody = '<html><head><meta http-equiv="Content-Type" content="text/html charset=utf-8"></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space;" class=""><br class=""><div><br class=""><blockquote type="cite" class=""><div class="">Inizio messaggio inoltrato:</div><br class="Apple-interchange-newline"><div style="margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px;" class=""><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif; color:rgba(0, 0, 0, 1.0);" class=""><b class="">Da: </b></span><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif;" class="">Materiale Sanitario CRICasatenovo &lt;<a href="mailto:materiale.sanitario@cricasatenovo.it" class="">materiale.sanitario@cricasatenovo.it</a>&gt;<br class=""></span></div><div style="margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px;" class=""><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif; color:rgba(0, 0, 0, 1.0);" class=""><b class="">Oggetto: </b></span><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif;" class=""><b class="">PROCEDURA RICARICA BATTERIA ELI 10</b><br class=""></span></div><div style="margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px;" class=""><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif; color:rgba(0, 0, 0, 1.0);" class=""><b class="">Data: </b></span><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif;" class="">13 aprile 2017 20:11:48 CEST<br class=""></span></div><div style="margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px;" class=""><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif; color:rgba(0, 0, 0, 1.0);" class=""><b class="">A: </b></span><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif;" class="">Presidente &lt;<a href="mailto:presidente@cricasatenovo.it" class="">presidente@cricasatenovo.it</a>&gt;<br class=""></span></div><div style="margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px;" class=""><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif; color:rgba(0, 0, 0, 1.0);" class=""><b class="">Rinviato-Da: </b></span><span style="font-family: -webkit-system-font, Helvetica Neue, Helvetica, sans-serif;" class="">&lt;<a href="mailto:jacopo.strada@mail.polimi.it" class="">jacopo.strada@mail.polimi.it</a>&gt;<br class=""></span></div><br class=""><div class=""><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" class=""><div style="font-size: 10pt; font-family: Verdana,Geneva,sans-serif" class=""><p class="">Visto il periodo di fermo dell\' apparecchio per non incorrere in errori, si allega procedura di ricarica batterie ELI 10.</p><p class="">Tali procedure sono disponibili anche in sede per prenderne visione.</p><p class="">A disposizione per delucidazioni</p><p class="">Grazie a tutti, Andrea</p><div class="">-- <br class=""><div class="pre" style="margin: 0; padding: 0; font-family: monospace">FUMAGALLI ANDREA<br class=""> RESPONSABILE MATERIALE SANITARIO - CRI Casatenovo<br class=""> <a href="http://www.cricasatenovo.it/" target="_blank" rel="noreferrer" class="">www.cricasatenovo.it</a><br class=""> <br class=""> Facebook <a href="http://www.facebook.com/cricasatenovo" target="_blank" rel="noreferrer" class="">www.facebook.com/cricasatenovo</a><br class=""> Twitter <a href="http://www.twitter.com/@cricasatenovo" target="_blank" rel="noreferrer" class="">www.twitter.com/@cricasatenovo</a><br class=""> Youtube <a href="http://www.youtube.com/cricasate" target="_blank" rel="noreferrer" class="">www.youtube.com/cricasate</a><br class=""> <br class=""> Note in ottemperanza al Decreto Legislativo 196/2003 sulla Tutela dei Dati Personali: il presente messaggio è rivolto unicamente all\'attenzione del destinatario ed il relativo contenuto potrebbe avere carattere riservato; ne è vietata la diffusione in qualunque modo eseguita. Nel caso in cui aveste ricevuto questa mail per errore, Vi invitiamo ad avvertire il mittente al più presto a mezzo posta elettronica e a distruggere il messaggio erroneamente ricevuto.</div></div></div></div></blockquote></div></body></html><html><head><meta http-equiv="Content-Type" content="text/html charset=us-ascii"></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space;" class=""><div><blockquote type="cite" class=""><div class=""></div></blockquote></div><br class=""></body></html>';

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
