# Patient

## 参考サイト
https://qiita.com/ucan-lab/items/56c9dc3cf2e6762672f4

## URL
http://localhost:10080

## クローン後

#### ビルド
```
docker-compose up -d --build
```

#### コンテナに入る
```
docker-compose exec app bash
```

#### Conposerをインストール
```
composer install
```

#### .envを作る
```
cp .env.example .env
```

#### アプリケーションキーを作成
```
php artisan key:generate
```

#### マイグレート
```
php artisan migrate
```

### JWT
```
composer require tymon/jwt-auth
```


## その他

#### composerがらみでメモリ不足になる場合
```
COMPOSER_MEMORY_LIMIT=-1 composer install
COMPOSER_MEMORY_LIMIT=-1 composer require tymon/jwt-auth
COMPOSER_MEMORY_LIMIT=-1 composer require laravel/ui
```