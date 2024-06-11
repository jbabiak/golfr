function Instance_Pricing(x, instances_premium, instances_enterprise) {
    document.getElementById("instances_standard").value = x;
    document.getElementById("instances_premium").value = x;
    document.getElementById("instances_enterprise").value = x;

    var sta_upgradeBtn = jQuery('#sta-upgrade-btn');
    var pre_upgradeBtn = jQuery('#pre-upgrade-btn');
    var ent_upgradeBtn = jQuery('#ent-upgrade-btn');

    const price_standard = { 1: "249", 2: "479", 3: "699", 4: "929", 5: "1079", 6: "1279", 7: "1479", 8: "1689", 9: "1899", 10: "2019", 15: "2349", 20: "2649", 30: "2949", 40: "3249", 50: "3349" };
    if(x==='10+'){

      document.getElementById("enterprise_price").innerHTML = "<br><br><a href=\"https://miniorange.com/contact\" class=\"mo_guide_text-color\" target=\"_blank\">Contact Us</a>";
      document.getElementById("premium_price").innerHTML = "<br><br><a href=\"https://miniorange.com/contact\" class=\"mo_guide_text-color\" target=\"_blank\">Contact Us</a>";
      document.getElementById("standard_price").innerHTML = "<br><br><a href=\"https://miniorange.com/contact\" class=\"mo_guide_text-color\" target=\"_blank\">Contact Us</a>";

      document.getElementById("standard_discount").style.display = "none";
      document.getElementById("premium_discount").style.display = "none";
      document.getElementById("enterprise_discount").style.display = "none";

      sta_upgradeBtn.hide();
      pre_upgradeBtn.hide();
      ent_upgradeBtn.hide();

    }else{
      sta_upgradeBtn.show();
      pre_upgradeBtn.show();
      ent_upgradeBtn.show();

      var actual_price_standard = 249 * x;
      var discount_standard = ((actual_price_standard - price_standard[x]) / actual_price_standard) * 100;
      discount_standard = Math.floor(discount_standard);
      if (discount_standard !== 0) {
        document.getElementById("standard_price").innerHTML = "<sup>$</sup><s style=\"color:red;\" >" + actual_price_standard + "</s> " + price_standard[x];
        document.getElementById("standard_discount").innerHTML = "<div style=\"color:red;\"><b>[ " + discount_standard + " % discount ] </b></div>";
        document.getElementById("standard_discount").style.display = "block";
      }else{
        document.getElementById("standard_price").innerHTML = "<sup>$</sup>" + price_standard[x];
        document.getElementById("standard_discount").style.display = "none";
      }
      const price_premium = { 1: "399", 2: "749", 3: "1119", 4: "1419", 5: " 1659", 6: "1899", 7: "2129", 8: "2349", 9: "2499", 10: "2599", 15: "2899", 20: "3199", 30: "3449", 40: "3699", 50: "3899" };
      var actual_price_premium = 399 * x;
      var discount_premium = ((actual_price_premium - price_premium[x]) / actual_price_premium) * 100;
      discount_premium = Math.floor(discount_premium);
      if (discount_premium !== 0) {
        document.getElementById("premium_price").innerHTML = "<sup>$</sup><s style=\"color:red;\" >" + actual_price_premium + "</s>" + price_premium[x];
        document.getElementById("premium_discount").innerHTML = "<div style=\"color:red;\"><b> [ " + discount_premium + " % discount ] </b></div>";
        document.getElementById("premium_discount").style.display = "block";
      }else{
        document.getElementById("premium_price").innerHTML = "<sup>$</sup>" + price_premium[x];
        document.getElementById("premium_discount").style.display = "none";
      }
      const price_enterprise = { 1: "449", 2: "849", 3: "1249", 4: "1549", 5: "1849", 6: "2099", 7: "2349", 8: "2549", 9: "2749", 10: "2899", 15: "3199", 20: "3499", 30: "3874", 40: "4249", 50: "4624" };
      var actual_price_enterprise = 449 * x;
      var discount_enterprise = ((actual_price_enterprise - price_enterprise[x]) / actual_price_enterprise) * 100;
      discount_enterprise = Math.floor(discount_enterprise);

      if (discount_standard !== 0){
        document.getElementById("enterprise_price").innerHTML = "<sup>$</sup><s style=\"color:red;\" >" + actual_price_enterprise + "</s>" + price_enterprise[x];
        document.getElementById("enterprise_discount").innerHTML = "<div style=\"color:red;\"><b>[ " + discount_enterprise + " % discount ] </b></div>";
        document.getElementById("enterprise_discount").style.display = "block";
      }else{
        document.getElementById("enterprise_price").innerHTML = "<sup>$</sup>" + price_enterprise[x];
        document.getElementById("enterprise_discount").style.display = "none";
      }
    }

}

var coll = document.getElementsByClassName("collapsible");
var i;

for (i = 0; i < coll.length; i++) {
    coll[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var content = this.nextElementSibling;
        if (content.style.display === "block") {
            content.style.display = "none";
        } else {
            content.style.display = "block";
        }
    });
}



