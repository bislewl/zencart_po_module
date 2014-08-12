<?php
require('fpdf/fpdf.php');

// Xavier Nicolay 2004
// Version 1.01 - Modified For Packing List


class INVOICE extends FPDF
{
// private variables
var $colonnes;
var $format;
var $angle=0;

// private functions
function RoundedRect($x, $y, $w, $h, $r, $style = '')
{
    $k = $this->k;
    $hp = $this->h;
    if($style=='F')
        $op='f';
    elseif($style=='FD' or $style=='DF')
        $op='B';
    else
        $op='S';
    $MyArc = 4/3 * (sqrt(2) - 1);
    $this->_out(sprintf('%.2f %.2f m',($x+$r)*$k,($hp-$y)*$k ));
    $xc = $x+$w-$r ;
    $yc = $y+$r;
    $this->_out(sprintf('%.2f %.2f l', $xc*$k,($hp-$y)*$k ));

    $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
    $xc = $x+$w-$r ;
    $yc = $y+$h-$r;
    $this->_out(sprintf('%.2f %.2f l',($x+$w)*$k,($hp-$yc)*$k));
    $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
    $xc = $x+$r ;
    $yc = $y+$h-$r;
    $this->_out(sprintf('%.2f %.2f l',$xc*$k,($hp-($y+$h))*$k));
    $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
    $xc = $x+$r ;
    $yc = $y+$r;
    $this->_out(sprintf('%.2f %.2f l',($x)*$k,($hp-$yc)*$k ));
    $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
    $this->_out($op);
}

function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
{
    $h = $this->h;
    $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->k, ($h-$y1)*$this->k,
                        $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
}



// public functions
function sizeOfText( $texte, $largeur )
{
$index = 0;
$nb_lines = 0;
$loop = TRUE;
if ($largeur != 0) {
while ( $loop )
{
$pos = strpos($texte, "\n");
if (!$pos)
{
$loop = FALSE;
$ligne = $texte;
}
else
{
$ligne = substr( $texte, $index, $pos);
$texte = substr( $texte, $pos+1 );
}
$length = floor( $this->GetStringWidth( $ligne ) );
$res = 1 + floor( $length / $largeur) ;
$nb_lines += $res;
} }
return $nb_lines;
}

// Company
function addSociete( $nom, $adresse )
{
    $nom = str_replace("{enter}","\n",$nom);
    $adresse = str_replace("{enter}","\n",$adresse);
    $x1 = 60;
    $y1 = 18;
    //Positionnement en bas
    $this->SetXY( $x1, $y1 );
    $this->SetFont('Arial','B',12);
    $length = $this->GetStringWidth( $nom );
    $this->Cell( $length, 2, $nom);
    $this->SetXY( $x1, $y1 + 4 );
    $this->SetFont('Arial','',10);
    $length = $this->GetStringWidth( $adresse );
    //Coordonnées de la société
    $lignes = $this->sizeOfText( $adresse, $length) ;
    $this->MultiCell($length, 4, $adresse);
}

// Label and number of invoice/estimate
function fact_dev( $libelle, $num )
{
    $r1  = $this->w - 85;
    $r2  = $r1 + 68;
    $y1  = 16;
    $y2  = $y1 - 8;
    $mid = ($r1 + $r2 ) / 2;
    
    $texte  = $libelle . $num;    
    $szfont = 12;
    $loop   = 0;
    
    while ( $loop == 0 )
    {
       $this->SetFont( "Helvetica", "B", $szfont );
       $sz = $this->GetStringWidth( $texte );
       if ( ($r1+$sz) > $r2 )
          $szfont --;
       else
          $loop ++;
    }

    $this->SetLineWidth(0.1);
    $this->SetFillColor(192);
    $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 2.5, 'DF');
    $this->SetXY( $r1+1, $y1+2);
    $this->Cell($r2-$r1 -1,5, $texte, 0, 0, "C" );
}


function addDate( $date )
{
    $r1  = $this->w - 85;
    $r2  = $r1 + 47;
    $y1  = 27;
    $y2  = $y1-10 ;
    $mid = $y1 + ($y2 / 2);
    $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
    $this->Line( $r1, $mid, $r2, $mid);
    $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+3 );
    $this->SetFont( "Helvetica", "B", 10);
    $this->Cell(10,5, PO_PDFP_DATE_TITLE, 0, 0, "C");
    $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+9 );
    $this->SetFont( "Helvetica", "", 10);
    $this->Cell(10,5,$date, 0,0, "C");
}

function addClient( $ref )
{
    $r1  = $this->w - 38;
    $r2  = $r1 + 21;
    $y1  = 27;
    $y2  = $y1-10;
    $mid = $y1 + ($y2 / 2);
    $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
    $this->Line( $r1, $mid, $r2, $mid);
    $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1+3 );
    $this->SetFont( "Helvetica", "B", 10);
    $this->Cell(10,5, PO_PDFP_ORDER_TITLE, 0, 0, "C");
    $this->SetXY( $r1 + ($r2-$r1)/2 - 5, $y1 + 9 );
    $this->SetFont( "Helvetica", "", 10);
    $this->Cell(10,5,$ref, 0,0, "C");
}


// Client address
function addClientShipAdresse( $adresse, $po_order_id = '' )
{
    $adresse = str_replace("{enter}","\n",$adresse);
    $r1     = $this->w - 85;
    $r2     = $r1 + 68;
    $y1     = 50;
    $po_order_id = '(For Order#'.$po_order_id.')';
    $shipping_header = PO_PDFP_SA_NAME.' '.$po_order_id;
     $this->SetXY( $r1, $y1);
$this->SetFont('Arial','B',10);
$length = $this->GetStringWidth($shipping_header);
    $this->Cell( $length, 2, $shipping_header);
    $this->SetXY( $r1, $y1 + 4 );
   $this->SetFont('Arial','',10);
    $this->MultiCell( 60, 4, $adresse);
}
function addClientBillAdresse( $adresse, $po_order_id = ''  )
{
    $adresse = str_replace("{enter}","\n",$adresse);
    $r1     = 15;
    $r2     = $r1 + 68;
    $y1     = 50;
    $po_order_id = '(For Order#'.$po_order_id.')';
    $shipping_header = PO_PDFP_FA_NAME.' '.$po_order_id;
    $this->SetXY( $r1, $y1);
$this->SetFont('Arial','B',10);
$length = $this->GetStringWidth($shipping_header);
    $this->Cell( $length, 2, $shipping_header);
    $this->SetXY( $r1, $y1 + 4 );
   $this->SetFont('Arial','',10);
    $this->MultiCell( 60, 4, $adresse);
}


function addReference($ref)
{
    $ref = str_replace("{enter}","\n",$ref);
    $this->SetFont( "Helvetica", "", 10);
    $length = $this->GetStringWidth( $ref );
    $r1  = 15;
    $r2  = $r1 + $length;
    $y1  = 235;
    $y2  = $y1+5;
    $this->SetXY( $r1 , $y1 );
    $this->MultiCell(180,4, $ref);

     
}

function addNotes($ref)
{
    $ref = str_replace("{enter}","\n",$ref);
    $begend = explode ("-",PO_PDFP_SHIP_COMMENTS_LOCATION);
    $this->SetFont( "Arial", "", 10);
    $length = $this->GetStringWidth( $ref );
    $r1  = $begend[0]+15;
    $r2  = $r1 + $length;
    $y1  = 200;
    $y2  = $y1+5;
    $this->SetXY( $r1 , $y1 );
    $this->MultiCell($begend[1]-$begend[0],4, $ref);
}

function addCols( $tab )
{
    global $colonnes;
    
    $r1  = 15;
    $r2  = $this->w - ($r1 * 2) ;
    $y1  = 80;
    $y2  = $this->h - 45 - $y1;
    $this->SetXY( $r1, $y1 );
    $this->Rect( $r1, $y1, $r2, $y2, "D");
    $this->Line( $r1, $y1+6, $r1+$r2, $y1+6);
    $colX = $r1;
    $colonnes = $tab;
    while ( list( $lib, $pos ) = each ($tab) )
    {
        $this->SetXY( $colX, $y1+2 );
        $this->Cell( $pos, 1, $lib, 0, 0, "C");
        $colX += $pos;
        $this->Line( $colX, $y1, $colX, $y1+$y2);
    }
}

function addLineFormat( $tab )
{
    global $format, $colonnes;
    
    while ( list( $lib, $pos ) = each ($colonnes) )
    {
        if ( isset( $tab["$lib"] ) )
            $format[ $lib ] = $tab["$lib"];
    }
}

function lineVert( $tab )
{
    global $colonnes;

    reset( $colonnes );
    $maxSize=0;
    while ( list( $lib, $pos ) = each ($colonnes) )
    {
        $texte = $tab[ $lib ];
        $longCell  = $pos -2;
        $size = $this->sizeOfText( $texte, $longCell );
        if ($size > $maxSize)
            $maxSize = $size;
    }
    return $maxSize;
}


function addLine( $ligne, $tab )
{
    global $colonnes, $format;

    $ordonnee     = 15;
    $maxSize      = $ligne;

    reset( $colonnes );
    while ( list( $lib, $pos ) = each ($colonnes) )
    {
        $longCell  = $pos -2;
        $texte     = $tab[ $lib ];
        $length    = $this->GetStringWidth( $texte );
        $tailleTexte = $this->sizeOfText( $texte, $length );
        $formText  = $format[ $lib ];
        $this->SetXY( $ordonnee, $ligne-1);
        $this->MultiCell( $longCell, 4 , $texte, 0, $formText);
        if ( $maxSize < ($this->GetY()  ) )
            $maxSize = $this->GetY() ;
        $ordonnee += $pos;
    }
    return ( $maxSize - $ligne );
}

}
?>