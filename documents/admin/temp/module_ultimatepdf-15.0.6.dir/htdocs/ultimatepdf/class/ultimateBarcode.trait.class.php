<?php 
/* Copyright (C) 2022		 Atoo-Net      <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */


trait UltimateBarcode
{
	public function ultimatebarcode(&$pdf, $product)
	{
		//Barcode style
		$styleBc = array(
			'position' => '',
			'align' => '',
			'stretch' => false,
			'fitwidth' => true,
			'cellfitalign' => '',
			'border' => false,
			'hpadding' => 'auto',
			'vpadding' => 'auto',
			'fgcolor' => array(0, 0, 0),
			'bgcolor' => false, //array(255,255,255),
			'text' => true,
			'font' => 'helvetica',
			'fontsize' => 8,
			'stretchtext' => 4
		);
		$curX = $this->getColumnContentXStart('desc');
		$posYAfterDescription = $pdf->GetY();
		// dysplay product barcode
		if ($product->barcode_type_code == 'EAN13') {
			//function get_ean13_key(string $digits)
			$digits = $product->barcode;
			if (strlen($digits) < 13) {
				$code = get_ean13_key($digits);
				$pdf->write1DBarcode($digits . $code, $product->barcode_type_code, $curX - 2, $posYAfterDescription, '', 12, 0.4, $styleBc, 'L');
			} elseif (strlen($digits) == 13) {
				$digits = substr($digits, 0, -1);
				$code = get_ean13_key($digits);
				$pdf->write1DBarcode($digits . $code, $product->barcode_type_code, $curX - 2, $posYAfterDescription, '', 12, 0.4, $styleBc, 'L');
			}
		} else {
			$pdf->write1DBarcode($product->barcode, $product->barcode_type_code, $curX - 2, $posYAfterDescription, '', 12, 0.4, $styleBc, 'L');
		} 	
	}

	public function ultimatecustomcode(&$pdf, $product, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$curX = $this->getColumnContentXStart('desc');
		$posYAfterDescription = $pdf->GetY();
		$pdf->SetFont('', '', $default_font_size - 4);
		
		$tmptxt = '';
		if (!empty($product->customcode))
			$tmptxt .= $outputlangs->transnoentitiesnoconv("CustomCode") . ': ' . $product->customcode;
		if (!empty($product->customcode) && !empty($product->country_code))
			$tmptxt .= ' - ';
		if (!empty($product->country_code)) {
			$tmptxt .= $outputlangs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($product->country_code, 0, $this->db, $outputlangs, 0);
			$pdf->writeHTMLCell($this->getColumnContentXStart('photo') - $this->getColumnContentXStart('desc'), 4, $curX - 1, $posYAfterDescription, dol_htmlentitiesbr($tmptxt), 0, 1, false, true, 'L', true);
		}
	}
}


