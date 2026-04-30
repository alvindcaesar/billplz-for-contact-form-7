(function () {
  document.addEventListener('wpcf7submit', function (event) {
    var detail = event.detail || {};
    var response = detail.apiResponse || {};
    var billplz = response.billplz || {};
    var redirectUrl = typeof billplz.redirect_url === 'string' ? billplz.redirect_url : '';

    if (detail.status !== 'payment_required' || !redirectUrl) {
      return;
    }

    window.location.assign(redirectUrl);
  }, false);
}());
