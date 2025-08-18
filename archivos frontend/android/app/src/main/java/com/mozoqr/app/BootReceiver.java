package com.mozoqr.app;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.Build;
import android.util.Log;

/**
 * Receiver que asegura que el servicio Firebase se reinicie automáticamente
 * cuando el dispositivo se reinicia o cuando la app se cierra.
 */
public class BootReceiver extends BroadcastReceiver {
    private static final String TAG = "BootReceiver";

    @Override
    public void onReceive(Context context, Intent intent) {
        String action = intent.getAction();
        Log.d(TAG, "🔄 Received broadcast: " + action);

        if (Intent.ACTION_BOOT_COMPLETED.equals(action) ||
            Intent.ACTION_MY_PACKAGE_REPLACED.equals(action) ||
            Intent.ACTION_PACKAGE_REPLACED.equals(action)) {
            
            Log.d(TAG, "🚀 Starting Firebase service automatically");
            startFirebaseService(context);
        }
    }

    private void startFirebaseService(Context context) {
        try {
            Intent serviceIntent = new Intent(context, MyFirebaseMessagingService.class);
            
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                context.startForegroundService(serviceIntent);
            } else {
                context.startService(serviceIntent);
            }
            
            Log.d(TAG, "✅ Firebase service started from receiver");
        } catch (Exception e) {
            Log.e(TAG, "❌ Error starting Firebase service from receiver", e);
        }
    }
}