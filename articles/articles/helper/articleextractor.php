<?php
/**
 * User: jacopostrada
 * Date: 21/04/17
 * Time: 19:59
 */
defined('_JEXEC') or die('Restricted access');

const emailSignature = "in ottempranza al Decreto Legislativo 196/2003";



function addAttacchmentsListToHtml(DOMDocument $dom, $attachmentsJson){
  $xpath = new DOMXPath($dom);
  $parent = $xpath -> query("//body");
  $child = $xpath -> query("//body/*[1]");
  $body = $dom -> getElementsByTagName('body')[0];
  $ulList = $dom->createElement('ul');
  foreach ($attachmentsJson as $attachmentName => $attachmentLink) {
    $fileExt = substr($attachmentName, strrpos($attachmentName, '.')+1);
    if($fileExt=="png" or $fileExt=="jpeg" or $fileExt=="jpg"){
      $imgElement = $dom -> createElement('img');
      $imgElement -> setAttribute('src', $attachmentLink);
      $parent ->item(0)-> insertBefore($imgElement, $child -> item(0));
    }
    $liElement = $dom -> createElement('li');
    $aElement = $dom -> createElement('a', $attachmentName);
    $aElement -> setAttribute('href', $attachmentLink);
    $liElement -> appendChild($aElement);
    $ulList -> appendChild($liElement);
  }
  $body -> appendChild($ulList);
}

function removeUselessElements(DOMNode $domNode)
{
    foreach ($domNode->childNodes as $node) {
        if ($node->tagName == "img") {
            $node->parentNode->removeChild($node);
        }
        //ogni nodo viene passato per accertarsi di togliere la signature
        if ($node->hasChildNodes()) {
            removeUselessElements($node);
        }
        if (strpos($node->textContent, emailSignature) !== false) {
            $domNode->parentNode->removeChild($domNode);
        }
    }
}

function formatArticle($mailBody, $attachmentsJsonString)
{
    $DOM = new DOMDocument();
    $DOM->loadHTML($mailBody);
    $attachmentsJson = json_decode($attachmentsJsonString, true);
    removeUselessElements($DOM);
    addAttacchmentsListToHtml($DOM, $attachmentsJson);

    return $DOM->saveHTML($DOM);
}




//  $emailBody = '<html> <head> <meta http-equiv="Content-Type"content="text/html charset=utf-8"> </head> <body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space;" class=""> <br class=""> <div><span style="font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" class="">Ciao a tutti,</span></div> <div><span style="font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" class=""><br class=""></span></div> <div><span style="font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" class="">come alcuni di voi già sanno, l\'A . S . D . FOR-CRI, in & egrave; collaborazione con la compagnia teatrale Ronzinante, organizza per il giorno </span ><strong class="" style = "font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" > venerdì 5 maggio alle ore 21.00 </strong ><span style = "font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" class="" > presso l\'</span><strong class="" style="font-family: Verdana, Geneva, sans-serif; font-size: 10pt;">Auditorium di Casatenovo</strong><span style="font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" class=""> uno </span><strong class="" style="font-family: Verdana, Geneva, sans-serif; font-size: 10pt;">spettacolo teatrale&nbsp;dal titolo "<span class="m_7414906159878263313gmail-m_3021351083141891534gmail-il"><span class="m_7414906159878263313gmail-il">CYRANO</span></span>&nbsp;DE&nbsp;<span class="m_7414906159878263313gmail-il">BERGERAC</span>...IN SALSA COMICA”</strong><span style="font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" class="">.&nbsp;</span></div> <div><span class="" style="font-family: Verdana, Geneva, sans-serif; font-size: 10pt;"><strong class=""><br class=""></strong></span></div> <div><span class="" style="font-family: Verdana, Geneva, sans-serif; font-size: 10pt;"><strong class="">Il ricavato della serata, il cui ingresso é a offerta libera, sarà devoluto al nostro Comitato</strong>, motivo in più per contribuire alla pubblicizzazione dell\'evento attraverso la & nbsp;</span ><span style = "font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" class="" > condivisione della locandina in allegato e del relativo </span ><a href = "https://www.facebook.com/events/673812129475872/" target = "_blank" class="" style = "font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" > evento Facebook </a ><span style = "font-family: Verdana, Geneva, sans-serif; font-size: 10pt;" class="" >.</span ><img name = "Spettacolo ASD For-CRI 5 Maggio.jpg" class="" apple - inline = "yes" id = "91B59300-BC70-4BBC-A892-D1E9B2CE4378" src = "cid:87C43750-BD56-43A4-8313-B5A40D17C85D@homenet.telecomitalia.it" ></div > <div > <blockquote type = "cite" class="" > <div style = "font-size: 10pt; font-family: Verdana,Geneva,sans-serif" class="" > <div class="" ></div > </div > </blockquote > </div > <br class="" > </body > </html > ';
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