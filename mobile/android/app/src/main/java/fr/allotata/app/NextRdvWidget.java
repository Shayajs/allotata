package fr.allotata.app;

import android.app.PendingIntent;
import android.appwidget.AppWidgetManager;
import android.appwidget.AppWidgetProvider;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.widget.RemoteViews;

public class NextRdvWidget extends AppWidgetProvider {
    @Override
    public void onUpdate(Context context, AppWidgetManager appWidgetManager, int[] appWidgetIds) {
        updateAll(context, appWidgetManager, appWidgetIds);
    }

    public static void updateAll(Context context, AppWidgetManager manager, int[] ids) {
        SharedPreferences prefs = context.getSharedPreferences(PocketSnapshotPlugin.PREFS, Context.MODE_PRIVATE);
        String titre = prefs.getString("titre", "Aucun rendez-vous");
        String quand = prefs.getString("quand", "");
        String lieu = prefs.getString("lieu", "");

        Intent launch = new Intent(context, MainActivity.class);
        PendingIntent pending = PendingIntent.getActivity(
            context,
            0,
            launch,
            PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE
        );

        for (int id : ids) {
            RemoteViews views = new RemoteViews(context.getPackageName(), R.layout.next_rdv_widget);
            views.setTextViewText(R.id.widget_title, titre);
            views.setTextViewText(R.id.widget_when, quand);
            views.setTextViewText(R.id.widget_place, lieu);
            views.setOnClickPendingIntent(R.id.widget_root, pending);
            manager.updateAppWidget(id, views);
        }
    }
}
