<?php

namespace App\Support;

use App\Models\App;

class AppContext
{
    protected static ?App $currentApp = null;

    protected static ?int $currentAppId = null;

    public static function set(App $app): void
    {
        static::$currentApp = $app;
        static::$currentAppId = $app->id;
    }

    public static function setId(int $id): void
    {
        static::$currentAppId = $id;
        static::$currentApp = null;
    }

    public static function get(): ?App
    {
        if (static::$currentApp === null && static::$currentAppId !== null) {
            static::$currentApp = App::withoutGlobalScopes()->find(static::$currentAppId);
        }

        return static::$currentApp;
    }

    public static function getId(): ?int
    {
        return static::$currentAppId;
    }

    public static function forget(): void
    {
        static::$currentApp = null;
        static::$currentAppId = null;
    }
}
