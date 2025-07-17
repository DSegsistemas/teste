<script>
$(function(){
    'use strict';
    
    // Filtros com submit de formulário
    $('#category_filter, #status_filter').on('change', function() {
        $('#filter_form').submit();
    });
    
    // Busca com delay para evitar muitos submits
    var searchTimeout;
    $('#search_product').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $('#filter_form').submit();
        }, 500);
    });
});

// Função para abrir modal de produto (sem AJAX)
function product_modal(id) {
    var url = admin_url + 'workshop/products';
    if (id && id > 0) {
        url += '?edit=' + id;
    } else {
        url += '?new=1';
    }
    window.location.href = url;
}

// Função para calcular preço de venda
function calculateSalePrice() {
    var purchasePrice = parseFloat($('#purchase_price').val()) || 0;
    var profitMargin = parseFloat($('#profit_margin').val()) || 0;
    var tax1Rate = 0;
    var tax2Rate = 0;
    
    // Obter taxa do imposto 1
    var tax1Id = $('#tax1').val();
    if (tax1Id) {
        var tax1Option = $('#tax1 option:selected');
        var tax1Text = tax1Option.text();
        var tax1Match = tax1Text.match(/\((\d+(?:\.\d+)?)%\)/);
        if (tax1Match) {
            tax1Rate = parseFloat(tax1Match[1]);
        }
    }
    
    // Obter taxa do imposto 2
    var tax2Id = $('#tax2').val();
    if (tax2Id) {
        var tax2Option = $('#tax2 option:selected');
        var tax2Text = tax2Option.text();
        var tax2Match = tax2Text.match(/\((\d+(?:\.\d+)?)%\)/);
        if (tax2Match) {
            tax2Rate = parseFloat(tax2Match[1]);
        }
    }
    
    // Calcular preço de venda
    var basePrice = purchasePrice * (1 + profitMargin / 100);
    var totalTaxRate = tax1Rate + tax2Rate;
    var salePrice = basePrice * (1 + totalTaxRate / 100);
    
    $('#sale_price').val(salePrice.toFixed(2));
}

// Função para deletar produto
function delete_product(id) {
    if (confirm('<?php echo _l("are_you_sure"); ?>')) {
        window.location.href = admin_url + 'workshop/delete_product/' + id;
    }
}
</script>