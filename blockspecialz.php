<?php
/**
 * Specials block (zapalm version): module for Prestashop 1.2-1.3
 *
 * @author zapalm <zapalm@ya.ru>
 * @copyright (c) 2010-2015, zapalm
 * @link http://prestashop.modulez.ru/en/frontend-features/21-specials-block-zapalm-version.html The module's homepage
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

if (!defined('PS_PRICE_ROUND_MODE'))
	define('PS_PRICE_ROUND_MODE', 2);

if (!defined('PS_ROUND_UP'))
	define('PS_ROUND_UP', 0);

if (!defined('PS_ROUND_DOWN'))
	define('PS_ROUND_DOWN', 1);

if (!defined('PS_ROUND_HALF'))
	define('PS_ROUND_HALF', 2);

class BlockSpecialz extends Module
{
	public function __construct()
	{
		$this->name = 'blockspecialz';
		$this->version = '1.1.0';
		$this->tab = 'Blocks';
		$this->author = 'zapalm';
		$this->need_instance = 0;
		$this->bootstrap = false;

		parent::__construct();

		$this->displayName = $this->l('Specials block (zapalm version)');
		$this->description = $this->l('Adds a block with list of products with lowered price.');
	}

	public function install()
	{
		return parent::install()
			&& $this->registerHook('rightColumn')
			&& Configuration::updateValue('SPECIALS_PRODUCTS_NBR', 4)
			&& Configuration::updateValue('SPECIALS_PRODUCTS_RANDOM', 1);
	}

	public function getContent()
	{
		$output = '<h2>'.$this->displayName.'</h2>';
		if (Tools::isSubmit('submitBlockSpecialsProducts'))
		{
			$productNbr = (int)Tools::getValue('productNbr');
			if ($productNbr === 0)
				$output .= '<div class="alert error">'.$this->l('Invalid number of products.').'</div>';
			else
			{
				Configuration::updateValue('SPECIALS_PRODUCTS_NBR', $productNbr);
				Configuration::updateValue('SPECIALS_PRODUCTS_RANDOM', (int)Tools::getValue('SPECIALS_PRODUCTS_RANDOM'));
				$output .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />'.$this->l('Settings updated').'</div>';
			}
		}

		return $output.$this->displayForm();
	}

	public function displayForm()
	{
		$output = '
			<fieldset style="width: 400px;">
				<legend><img src="'.$this->_path.'logo.gif" alt="" title="" />'.$this->l('Settings').'</legend>
				<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
					<label>'.$this->l('Products displayed').'</label>
					<div class="margin-form">
						<input type="text" name="productNbr" value="'.(int)Configuration::get('SPECIALS_PRODUCTS_NBR').'" />
						<p class="clear">'.$this->l('Set the number of products to be displayed in this block').'</p>
					</div>
					<label>'.$this->l('Show specials randomly').'</label>
					<div class="margin-form">
						<input type="checkbox" name="SPECIALS_PRODUCTS_RANDOM"  value="1" '.(Configuration::get('SPECIALS_PRODUCTS_RANDOM') ? 'checked="checked"' : '').' />
						<p class="clear">'.$this->l('Check it, if you whant to show specials randomly').'</p>
					</div>
					<center><input type="submit" name="submitBlockSpecialsProducts" value="'.$this->l('Save').'" class="button" /></center>
				</form>
			</fieldset>
			<br class="clear">
		';

		return $output;
	}

	public function getSpecials($id_lang, $nbProducts = 4, $random = false, $randomNumberProducts = 4)
	{
		global $cookie;

		$sql = '
			SELECT
				p.*,
				pl.`description`,
				pl.`description_short`,
				pl.`link_rewrite`,
				pl.`meta_description`,
				pl.`meta_keywords`,
				pl.`meta_title`,
				pl.`name`,
				p.`ean13`,
				i.`id_image`,
				il.`legend`,
				t.`rate`
			FROM `'._DB_PREFIX_.'product` p
			LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int)$id_lang.')
			LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND i.`cover` = 1)
			LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
			LEFT JOIN `'._DB_PREFIX_.'tax` t ON t.`id_tax` = p.`id_tax`
			WHERE (`reduction_price` > 0 OR `reduction_percent` > 0)
			AND p.`active` = 1
			AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_group` cg
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
				WHERE cg.`id_group` '.((int)$cookie->id_customer < 1 ? ' = 1' : 'IN (SELECT id_group FROM '._DB_PREFIX_.'customer_group WHERE id_customer = '.(int)$cookie->id_customer.')').'
			)
		';

		if ($random === true)
		{
			$sql .= ' ORDER BY RAND()';
			$sql .= ' LIMIT 0, '.(int)$randomNumberProducts;
		}
		else
		{
			$sql .= ' ORDER BY price';
			$sql .= ' LIMIT 0, '.(int)$nbProducts;
		}

		$res = Db::getInstance()->ExecuteS($sql);

		if (!$res)
			return false;

		return Product::getProductsProperties($id_lang, $res);
	}

	public function hookRightColumn($params)
	{
		global $smarty;

		$nb = (int)Configuration::get('SPECIALS_PRODUCTS_NBR');
		if ((int)Configuration::get('SPECIALS_PRODUCTS_RANDOM'))
			$specials = $this->getSpecials((int)$params['cookie']->id_lang, ($nb ? $nb : 4), true, ($nb ? $nb : 4));
		else
			$specials = $this->getSpecials((int)$params['cookie']->id_lang, ($nb ? $nb : 4));

		foreach ($specials as $k => $special)
			$special['priceWithoutReduction_tax_excl'] = $this->ps_round($special['price_without_reduction'] / (1 + $special['rate'] / 100), 2);

		$smarty->assign(array(
			'specials' => $specials,
			'specials_count' => count($specials),
			'mediumSize' => Image::getSize('medium')
		));

		return $this->display(__FILE__, 'blockspecialz.tpl');
	}

	public function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}

	public function ceilf($value, $precision = 0)
	{
		$precisionFactor = $precision == 0 ? 1 : pow(10, $precision);
		$tmp = $value * $precisionFactor;
		$tmp2 = (string)$tmp;
		// If the current value has already the desired precision
		if (strpos($tmp2, '.') === false)
			return ($value);
		if ($tmp2[strlen($tmp2) - 1] == 0)
			return $value;
		return ceil($tmp) / $precisionFactor;
	}

	public function floorf($value, $precision = 0)
	{
		$precisionFactor = $precision == 0 ? 1 : pow(10, $precision);
		$tmp = $value * $precisionFactor;
		$tmp2 = (string)$tmp;
		// If the current value has already the desired precision
		if (strpos($tmp2, '.') === false)
			return ($value);
		if ($tmp2[strlen($tmp2) - 1] == 0)
			return $value;
		return floor($tmp) / $precisionFactor;
	}

	public function ps_round($value, $precision = 0)
	{
		$method = (int)Configuration::get('PS_PRICE_ROUND_MODE');
		if (!$method)
			$method = (int)PS_PRICE_ROUND_MODE;

		if ($method == PS_ROUND_UP)
			return $this->ceilf($value, $precision);
		elseif ($method == PS_ROUND_DOWN)
			return $this->floorf($value, $precision);

		return round($value, $precision);
	}
}