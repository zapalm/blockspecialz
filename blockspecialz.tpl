<!-- MODULE Block specials -->
<div id="viewed-products_block_left" class="block products_block">
	<h4><a href="{$base_dir}prices-drop.php" title="{l s='Specials' mod='blockspecialz'}">{l s='Specials' mod='blockspecialz'}</a></h4>
	<div class="block_content">
	{if $specials}
		<ul class="products clearfix">
			{foreach from=$specials item='special' name='specialProducts'}
				<li class="clearfix{if $smarty.foreach.specialProducts.last} last_item{elseif $smarty.foreach.specialProducts.first} first_item{else} item{/if}">
					<a href="{$special.link}"><img src="{$link->getImageLink($special.link_rewrite, $special.id_image, 'medium')}" alt="{$special.legend|escape:html:'UTF-8'}" height="{$mediumSize.height}" width="{$mediumSize.width}" title="{$special.name|escape:html:'UTF-8'}" /></a>
					<h5><a href="{$special.link}" title="{$special.name|escape:html:'UTF-8'}">{$special.name|escape:html:'UTF-8'|truncate:14:'...'}</a></h5>
					<br>
					<span class="price-discount">{if !$priceDisplay}{displayWtPrice p=$special.price_without_reduction}{else}{displayWtPrice p=$special.priceWithoutReduction_tax_excl}{/if}</span>
					<br>
					{if $special.reduction_percent}<span class="reduction">(-{$special.reduction_percent}%)</span>{/if}
					<br>
					<span class="price">{if !$priceDisplay}{displayWtPrice p=$special.price}{else}{displayWtPrice p=$special.price_tax_exc}{/if}</span>
					{if $specials_count > 1 && $special.quantity > 0}
						<br>
						{l s='Remains:' mod='blockspecialz'} <span class="quantity">{$special.quantity}</span>
					{/if}
				</li>
			{/foreach}
		</ul>
		<p>
			{if $specials_count == 1 && $specials[0].quantity}
				<a href="{$specials[0].link}" title="{l s='Product left' mod='blockspecialz'}" class="button_large">{l s='Product left:' mod='blockspecialz'} {$specials[0].quantity}</a>
			{else}
				<a href="{$base_dir}prices-drop.php" title="{l s='All specials' mod='blockspecialz'}" class="button_large">{l s='All specials' mod='blockspecialz'}</a>
			{/if}
		</p>
	{else}
		<p>{l s='No specials at this time' mod='blockspecialz'}</p>
	{/if}
	</div>
</div>
<!-- /MODULE Block specials -->