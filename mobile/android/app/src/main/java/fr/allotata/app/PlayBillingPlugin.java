package fr.allotata.app;

import androidx.annotation.NonNull;

import com.android.billingclient.api.BillingClient;
import com.android.billingclient.api.BillingClientStateListener;
import com.android.billingclient.api.BillingFlowParams;
import com.android.billingclient.api.BillingResult;
import com.android.billingclient.api.PendingPurchasesParams;
import com.android.billingclient.api.ProductDetails;
import com.android.billingclient.api.Purchase;
import com.android.billingclient.api.QueryProductDetailsParams;
import com.android.billingclient.api.QueryPurchasesParams;
import com.getcapacitor.JSArray;
import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

import java.util.Collections;
import java.util.List;

@CapacitorPlugin(name = "PlayBilling")
public class PlayBillingPlugin extends Plugin {
    private BillingClient billingClient;
    private PluginCall pendingPurchase;

    @Override
    public void load() {
        billingClient = BillingClient.newBuilder(getContext())
            .setListener(this::onPurchasesUpdated)
            .enablePendingPurchases(
                PendingPurchasesParams.newBuilder().enableOneTimeProducts().build()
            )
            .build();
    }

    @PluginMethod
    public void isAvailable(PluginCall call) {
        JSObject ret = new JSObject();
        ret.put("available", true);
        call.resolve(ret);
    }

    @PluginMethod
    public void purchase(PluginCall call) {
        String productId = call.getString("productId");
        if (productId == null || productId.isEmpty()) {
            call.reject("productId requis");
            return;
        }

        String productType = "inapp".equals(call.getString("productType"))
            ? BillingClient.ProductType.INAPP
            : BillingClient.ProductType.SUBS;

        pendingPurchase = call;
        ensureReady(() -> launchPurchase(productId, productType, call), call);
    }

    @PluginMethod
    public void restore(PluginCall call) {
        ensureReady(() -> {
            QueryPurchasesParams params = QueryPurchasesParams.newBuilder()
                .setProductType(BillingClient.ProductType.SUBS)
                .build();
            billingClient.queryPurchasesAsync(params, (result, purchases) -> {
                if (result.getResponseCode() != BillingClient.BillingResponseCode.OK) {
                    call.reject(result.getDebugMessage());
                    return;
                }
                JSArray list = new JSArray();
                for (Purchase purchase : purchases) {
                    list.put(purchaseToJson(purchase));
                }
                JSObject ret = new JSObject();
                ret.put("purchases", list);
                call.resolve(ret);
            });
        }, call);
    }

    private void launchPurchase(String productId, String productType, PluginCall call) {
        QueryProductDetailsParams.Product product = QueryProductDetailsParams.Product.newBuilder()
            .setProductId(productId)
            .setProductType(productType)
            .build();

        QueryProductDetailsParams params = QueryProductDetailsParams.newBuilder()
            .setProductList(Collections.singletonList(product))
            .build();

        billingClient.queryProductDetailsAsync(params, (result, details) -> {
            if (result.getResponseCode() != BillingClient.BillingResponseCode.OK) {
                pendingPurchase = null;
                call.reject(result.getDebugMessage());
                return;
            }

            if (details == null || details.isEmpty()) {
                pendingPurchase = null;
                call.reject("Produit Google Play introuvable : " + productId);
                return;
            }

            ProductDetails productDetails = details.get(0);
            BillingFlowParams.ProductDetailsParams.Builder item =
                BillingFlowParams.ProductDetailsParams.newBuilder().setProductDetails(productDetails);

            if (productDetails.getSubscriptionOfferDetails() != null
                && !productDetails.getSubscriptionOfferDetails().isEmpty()) {
                item.setOfferToken(productDetails.getSubscriptionOfferDetails().get(0).getOfferToken());
            }

            BillingFlowParams flowParams = BillingFlowParams.newBuilder()
                .setProductDetailsParamsList(Collections.singletonList(item.build()))
                .build();

            BillingResult launch = billingClient.launchBillingFlow(getActivity(), flowParams);
            if (launch.getResponseCode() != BillingClient.BillingResponseCode.OK) {
                pendingPurchase = null;
                call.reject(launch.getDebugMessage());
            }
        });
    }

    private void onPurchasesUpdated(@NonNull BillingResult billingResult, List<Purchase> purchases) {
        PluginCall call = pendingPurchase;
        pendingPurchase = null;
        if (call == null) {
            return;
        }

        if (billingResult.getResponseCode() == BillingClient.BillingResponseCode.USER_CANCELED) {
            call.reject("Achat annulé");
            return;
        }

        if (billingResult.getResponseCode() != BillingClient.BillingResponseCode.OK
            || purchases == null
            || purchases.isEmpty()) {
            call.reject(billingResult.getDebugMessage());
            return;
        }

        call.resolve(purchaseToJson(purchases.get(0)));
    }

    private JSObject purchaseToJson(Purchase purchase) {
        JSObject json = new JSObject();
        json.put("purchaseToken", purchase.getPurchaseToken());
        json.put("orderId", purchase.getOrderId());
        List<String> products = purchase.getProducts();
        json.put("productId", products.isEmpty() ? "" : products.get(0));
        return json;
    }

    private void ensureReady(Runnable next, PluginCall call) {
        if (billingClient.isReady()) {
            next.run();
            return;
        }

        billingClient.startConnection(new BillingClientStateListener() {
            @Override
            public void onBillingSetupFinished(@NonNull BillingResult billingResult) {
                if (billingResult.getResponseCode() == BillingClient.BillingResponseCode.OK) {
                    next.run();
                    return;
                }
                pendingPurchase = null;
                call.reject(billingResult.getDebugMessage());
            }

            @Override
            public void onBillingServiceDisconnected() {
                // Reconnect on next call
            }
        });
    }
}
