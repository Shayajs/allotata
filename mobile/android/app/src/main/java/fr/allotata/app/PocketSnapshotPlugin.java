package fr.allotata.app;

import android.appwidget.AppWidgetManager;
import android.content.ComponentName;
import android.content.Context;
import android.content.SharedPreferences;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

@CapacitorPlugin(name = "PocketSnapshot")
public class PocketSnapshotPlugin extends Plugin {
    public static final String PREFS = "pocket_snapshot";

    @PluginMethod
    public void saveNextRdv(PluginCall call) {
        Context context = getContext();
        SharedPreferences prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE);
        prefs.edit()
            .putString("titre", call.getString("titre", "Aucun rendez-vous"))
            .putString("quand", call.getString("quand", ""))
            .putString("lieu", call.getString("lieu", ""))
            .apply();

        AppWidgetManager manager = AppWidgetManager.getInstance(context);
        int[] ids = manager.getAppWidgetIds(new ComponentName(context, NextRdvWidget.class));
        if (ids.length > 0) {
            NextRdvWidget.updateAll(context, manager, ids);
        }

        JSObject ret = new JSObject();
        ret.put("ok", true);
        call.resolve(ret);
    }
}
