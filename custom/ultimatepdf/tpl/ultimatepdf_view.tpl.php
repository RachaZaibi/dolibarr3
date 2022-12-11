<?php
/* Copyright (C) 2009-2012 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2021 Philippe Grand <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<?php
if (isset($this->tpl['action_delete'])) {
	echo $this->tpl['action_delete'];
}
?>

<table class="noborder">

	<tr class="liste_titre">

		<td><?php echo $langs->trans('ID'); ?></td>
		<td style="text-align:left"><?php echo $langs->trans('Label'); ?></td>
		<td style="text-align:left"><?php echo $langs->trans('Description'); ?></td>
		<td style="text-align:left"><?php echo $langs->trans('BorderType'); ?></td>
		<td style="text-align:left"><?php echo $langs->trans('Bgcolor'); ?></td>
		<td style="text-align:left"><?php echo $langs->trans('Bordercolor'); ?></td>
		<td style="text-align:left"><?php echo $langs->trans('Textcolor'); ?></td>
		<td style="text-align:left"><?php echo $langs->trans('Footertextcolor'); ?></td>
		<td style="text-align:center"><?php echo $langs->trans('Status'); ?></td>
		<td style="text-align:center" colspan="2">&nbsp;</td>
	</tr>

	<?php
	//var_dump($this->tpl['designs']);exit;
		foreach ($this->tpl['designs'] as $design) {
	?>

		<tr class="oddeven">
			<td><?php echo $design->id; ?></td>
			<td style="text-align:left"><?php echo $design->label; ?></td>
			<td style="text-align:left"><?php echo $design->description; ?></td>
			<td style="text-align:left"><?php echo ($design->options['dashdotted'] ? $langs->trans('Dashdotted') : $langs->trans('ContinuousLines')); ?></td>
			<?php $bgcolor = html2rgb($design->options['bgcolor']);
			// Set text color to black or white
			$codecolor = join(',', colorStringToArray($bgcolor));
			$tmppart = explode(',', $codecolor);
			$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
			if ($tmpval <= 460) {
				$codecolor = '255,255,255';
			} else {
				$codecolor = '0,0,0';
			} ?>
			<td style="text-align:left;background-color:rgb(<?php echo implode(",", $bgcolor); ?>);color: rgb(<?php echo $codecolor; ?>);"><?php echo implode(",", $bgcolor); ?></td>
			<?php $bordercolor = html2rgb($design->options['bordercolor']);
			// Set text color to black or white
			$codecolor = join(',', colorStringToArray($bordercolor));
			$tmppart = explode(',', $codecolor);
			$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
			if ($tmpval <= 460) $codecolor = '255,255,255';
			else $codecolor = '0,0,0'; ?>
			<td style="text-align:left;background-color:rgb(<?php echo implode(",", $bordercolor); ?>);color: rgb(<?php echo $codecolor; ?>);"><?php echo implode(",", $bordercolor); ?></td>
			<?php $textcolor = html2rgb($design->options['textcolor']);
			// Set text color to black or white
			$codecolor = join(',', colorStringToArray($textcolor));
			$tmppart = explode(',', $codecolor);
			$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
			if ($tmpval <= 460) $codecolor = '255,255,255';
			else $codecolor = '0,0,0'; ?>
			<td style="text-align:left;background-color:rgb(<?php echo implode(",", $textcolor); ?>);color: rgb(<?php echo $codecolor; ?>);"><?php echo implode(",", $textcolor); ?></td>
			<?php $footertextcolor = html2rgb($design->options['footertextcolor']);
			// Set text color to black or white
			$codecolor = join(',', colorStringToArray($footertextcolor));
			$tmppart = explode(',', $codecolor);
			$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
			if ($tmpval <= 460) $codecolor = '255,255,255';
			else $codecolor = '0,0,0'; ?>
			<td style="text-align:left;background-color:rgb(<?php echo implode(",", $footertextcolor); ?>);color: rgb(<?php echo $codecolor; ?>);"><?php echo implode(",", $footertextcolor); ?></td>
			<td style="text-align:center" width="30">
				<?php
				if ($design->active) {
					echo '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $design->id . '&amp;action=setactive&amp;value=0">' . $this->tpl['img_on'] . '</a>';
				} else {
					echo '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $design->id . '&amp;action=setactive&amp;value=1">' . $this->tpl['img_off'] . '</a>';
				}
				?>
			</td>

			<td style="text-align:center" width="20">
				<?php echo '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $design->id . '&amp;action=edit">' . $this->tpl['img_modify'] . '</a>'; ?>
			</td>
			<td style="text-align:center" width="20">
				<?php if ($design->id != $conf->global->ULTIMATE_DESIGN) echo '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $design->id . '&amp;action=delete">' . $this->tpl['img_delete'] . '</a>'; ?>
			</td>
		</tr>
	<?php
	}
	?>

</table>
</div>

<div class="tabsAction">
	<a class="butAction" href="<?php echo $_SERVER["PHP_SELF"]; ?>?action=create"><?php echo $langs->trans('AddDesign'); ?></a>
</div>

<!-- END PHP TEMPLATE -->