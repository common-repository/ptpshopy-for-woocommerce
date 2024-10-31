!(function () {
  "use strict";
  const t = window.wp.element,
    e = window.wp.htmlEntities,
    a = window.wp.i18n,
    n = window.wc.wcBlocksRegistry,
    i = window.wc.wcSettings;
  const l = () => {
    const t = (0, i.getSetting)("ptpshopy_data", null);
    if (!t) throw new Error("PTPShopy initialization data is not available");
    return t;
  };
  let o;
  const r = () => (0, e.decodeEntities)(l()?.description || "");
  (0, n.registerPaymentMethod)({
    name: "ptpshopy",
    label: (0, t.createElement)(
      () =>
        (0, t.createElement)("img", {
          src: l()?.logo_url,
          alt: l()?.title,
        }),
      null
    ),
    ariaLabel: (0, a.__)(
      "PTPShopy payment method",
      "woocommerce-gateway-ptpshopy"
    ),
    canMakePayment: () => !0,
    content: (0, t.createElement)(r, null),
    edit: (0, t.createElement)(r, null),
    supports: {
      features: null !== (o = l()?.supports) && void 0 !== o ? o : [],
    },
  });
})();
