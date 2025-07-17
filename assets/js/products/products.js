// Função para abrir modal de produto
function product_modal(id) {
    var url = admin_url + 'workshop/load_product_modal';
    if (id) {
        url += '/' + id;
    }
    window.location.href = url;
}

// Função para calcular o preço de venda automaticamente
function calculateSalePrice() {
    var purchasePrice = parseFloat($('#purchase_price').val()) || 0;
    var profitMargin = parseFloat($('#profit_margin').val()) || 0;
    var tax1Id = $('#tax1').val();
    var tax2Id = $('#tax2').val();
    
    // Calcular margem de lucro
    var profitAmount = (purchasePrice * profitMargin) / 100;
    
    // Calcular impostos
    var tax1Rate = tax1Id ? (taxRates[tax1Id] || 0) : 0;
    var tax2Rate = tax2Id ? (taxRates[tax2Id] || 0) : 0;
    
    var basePrice = purchasePrice + profitAmount;
    var tax1Amount = (basePrice * tax1Rate) / 100;
    var tax2Amount = (basePrice * tax2Rate) / 100;
    
    // Preço final
    var salePrice = basePrice + tax1Amount + tax2Amount;
    
    // Atualizar campo de preço de venda
    $('#sale_price').val(salePrice.toFixed(2));
}

// Inicializar eventos quando o documento estiver pronto
$(document).ready(function() {
    // Inicializar DataTable para produtos se existir
    if ($('#products_table').length > 0) {
        $('#products_table').DataTable({
            "order": [[0, "desc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Portuguese-Brasil.json"
            }
        });
    }
});