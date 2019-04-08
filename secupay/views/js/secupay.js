/*
 * secupay Payment Module
 * @author    secupay AG
 * @copyright 2019, secupay AG
 * @license   LICENSE.txt
 * @category  Payment
 *
 * Description:
 *  Prestashop Plugin for integration of secupay AG payment services
 */
$(function () {
  $('#secupay').show();

  $('.loadiframe').on('click', function () {
    //var src = $(this).data('src');
    $('#secupay').show();
    //$('#frame').attr('src', src);
  });
});