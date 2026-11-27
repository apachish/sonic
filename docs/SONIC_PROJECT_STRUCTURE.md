# Sonic WordPress Plugin — ساختار پروژه و سیستم پرامت

> نسخه پلاگین: 1.00  
> نویسنده: Niklink  
> تاریخ مستند: 2026-07-11

---

## ۱. خلاصه معماری

Sonic یک **کلاینت وردپرس** است. پرامت‌های واقعی AI و فراخوانی GPT/Gemini روی **سرور Niklink** انجام می‌شود، نه داخل این پلاگین.

```
┌─────────────────┐     AJAX (wp_ajax)      ┌──────────────────┐
│  Admin UI       │ ──────────────────────► │  ApiHandler.php  │
│  (PHP + JS)     │                         │  data_sanitizer  │
└─────────────────┘                         └────────┬─────────┘
                                                     │ POST JSON + api-key
                                                     ▼
                                          ┌──────────────────────┐
                                          │  Niklink API Server  │
                                          │  niklinkagency.com   │
                                          │  (پرامت + AI اینجاست)│
                                          └────────┬─────────────┘
                                                     │ async callback
                                                     ▼
                                          ┌──────────────────────┐
                                          │  Receive.php         │
                                          │  /sonic/v1/post/update│
                                          └────────┬─────────────┘
                                                     │
                                                     ▼
                                          ┌──────────────────────┐
                                          │  PostPublisher.php   │
                                          │  آپدیت پست وردپرس    │
                                          └──────────────────────┘
```

---

## ۲. ساختار فایل‌ها

```
sonic/
├── sonic.php                    # نقطه ورود پلاگین، activation hook
├── composer.json                # PSR-4 autoload: NIKSEO\src\, NIKSEO\callbacks\
│
├── src/                         # منطق اصلی
│   ├── Base.php                 # ثابت‌ها، endpointها، bootstrap
│   ├── ApiHandler.php           # AJAX handler، sanitize، ارسال به API
│   ├── Receive.php              # REST callback دریافت محتوا از Niklink
│   └── PostPublisher.php        # CRUD پست، تصویر شاخص، تصاویر داخل مقاله
│
├── callbacks/
│   ├── Menu.php                 # منوهای ادمین وردپرس
│   └── NikCallBacks.php         # enqueue اسکریپت، render صفحات admin
│
├── admin/                       # Viewهای پنل ادمین
│   ├── main.php                 # تنظیمات (API Key، Site Description، SEO Plugin)
│   ├── ai-content-creator.php   # فرم ساخت مقاله (۴ مرحله)
│   ├── keyword_research.php     # Assistant — پیشنهاد عنوان
│   └── posts.php                # لیست پست‌ها + آپدیت
│
├── assets/
│   ├── css/admin-css.css
│   ├── js/admin-js.js           # فرم‌ها، AJAX، polling وضعیت
│   └── img/
│
├── lang/
│   ├── sonic-fa_IR.po
│   └── sonic-de_DE.po
│
└── vendor/                      # Composer autoload
```

---

## ۳. کلاس‌ها و مسئولیت‌ها

| کلاس | فایل | مسئولیت |
|------|------|---------|
| `Base` | `src/Base.php` | تعریف endpointها، MODELS، HINTS، bootstrap |
| `ApiHandler` | `src/ApiHandler.php` | `wp_ajax_data_receiver`، sanitize، ارسال به Niklink |
| `Receive` | `src/Receive.php` | `POST /wp-json/sonic/v1/post/update` |
| `PostPublisher` | `src/PostPublisher.php` | `publish()`, `update()`, `get_posts()`, تصاویر |
| `Menu` | `callbacks/Menu.php` | ۴ صفحه منو در ادمین |
| `NikCallBacks` | `callbacks/NikCallBacks.php` | asset enqueue، render template |

---

## ۴. Endpointهای خارجی (Niklink API)

| ثابت | URL | کاربرد |
|------|-----|--------|
| `NIK_CONTENT_CREATE_ROUTE` | `{SONIC_END_POINT}/wp-json/nik/v1/seo` | ساخت/آپدیت مقاله |
| `NIK_KEYWORD_RESEARCH_ROUTE` | `{SONIC_END_POINT}/wp-json/nik/v1/keyword` | پیشنهاد عنوان |
| `NIK_USER_ASSET_ROUTE` | `{SONIC_END_POINT}/wp-json/nik/v1/user` | اعتبار کلید + توکن |
| `NIK_TOOLS` | `{SONIC_END_POINT}/wp-json/nik/v1/tools` | تولید Site Description |

**`SONIC_END_POINT`:**
- Production: `https://niklinkagency.com`
- Dev (`GHOST=true`): `http://dev.me`

---

## ۵. Endpoint داخلی (WordPress REST)

| Route | Method | Handler | کاربرد |
|-------|--------|---------|--------|
| `/wp-json/sonic/v1/post/update` | POST | `Receive::submit_post_handler` | دریافت HTML مقاله از Niklink |

**Header لازم:** `api-key`  
**Body نمونه callback:**
```json
{
  "postID": 123,
  "postContent": "<h2>...</h2><p>...</p>",
  "message": "OK",
  "image": "https://...",
  "articleImages": [],
  "metaDescription": "..."
}
```

---

## ۶. AJAX Actions (`wp_ajax_data_receiver`)

| `type` | Handler | توضیح |
|--------|---------|-------|
| `single-article` | `handle_request()` (default) | ساخت مقاله جدید |
| `update` | `update_article()` | آپدیت کل/جزئی پست |
| `cancel-update` | `cancel_update()` | لغو آپدیت |
| `check_status` | `return_processing_posts()` | لیست پست‌های `processing` |
| `keyword_research` | `return_keyword_research()` | پیشنهاد عنوان |
| `site_description` | `return_site_description()` | تولید توضیح سایت |
| `disable_hint` | `disable_hint()` | غیرفعال کردن hint |

---

## ۷. سیستم پرامت — نکته مهم

### پرامت داخل پلاگین **وجود ندارد**

هیچ فایلی با system prompt، template پرامت، یا فراخوانی مستقیم OpenAI/Gemini در این repo نیست.

پلاگین فقط **پارامترهای ورودی** را جمع‌آوری و به API می‌فرستد. سرور Niklink این پارامترها را به پرامت AI تبدیل می‌کند.

### جریان داده پرامت

```
فرم HTML → admin-js.js → POST admin-ajax.php
    → ApiHandler::data_sanitizer()  (rename فیلدها)
    → json_encode($data)
    → POST Niklink API
    → [سرور: ساخت پرامت + AI]
    → callback با postContent
```

---

## ۸. فیلدهای ورودی پرامت (ساخت مقاله)

| فیلد فرم | فیلد API | نوع | اجباری | توضیح |
|----------|----------|-----|--------|-------|
| `title` | `title` | string | ✅ | عنوان مقاله |
| `slug` | `slug` | string | ❌ | نامک URL |
| `keywords` (JS tags) | `keywords` | array | ❌ | کلمات کلیدی |
| `cats[]` | `cats` | array | ❌ | دسته‌بندی |
| `tone` | `tone` | string | ❌ | `warm` یا `official` |
| `min-words` | `minWords` | number | ❌ | حداقل کلمات |
| `max-words` | `maxWords` | number | ❌ | حداکثر کلمات |
| `audience` | `audience` | string | ❌ | مخاطب هدف |
| `relevant-title[]` + `relevant-link[]` | `customLinks` | array | ❌ | لینک‌های داخلی |
| `article-img` | `articleImages` | string/number | ❌ | تعداد تصویر داخل مقاله |
| `meta-description-input` | `metaDescription` | 1/0 | ❌ | meta description (اگر SEO plugin فعال) |
| `featured-image` | `featuredImage` | 1/0 | ❌ | تصویر شاخص (+۱ توکن) |
| `model` | `model` | string | ❌ | `gpt` یا `gemini` |
| `lang` | `lang` | string | ❌ | locale مثل `fa_IR` |
| `post-status` | `post-status` | string | ❌ | `publish` / `draft` |
| `custom-prompt` | `customPrompt` | string | ❌ | **تنها پرامت قابل ویرایش توسط کاربر** |
| — | `siteDescription` | string | ❌ | از `wp_options` |
| — | `origin` | string | ✅ | `home_url()` — آدرس callback |
| — | `postID` | int | ✅ | ID پست موقت وردپرس |

### فیلدهای آپدیت مقاله

| فیلد | توضیح |
|------|-------|
| `update` | `1` — فلگ آپدیت |
| `partialUpdate` | `1` — آپدیت جزئی |
| `postContent` | محتوای فعلی پست |
| `postId` | آرایه ID پست‌ها |

### فیلدهای Keyword Research

| فیلد | توضیح |
|------|-------|
| `keywordResearch` | `1` |
| `siteDescription` | از تنظیمات |
| `blogTitles` | عناوین پست‌های منتشرشده |
| `lang` | زبان |
| `model` | مدل AI |

### فیلدهای Site Description

| فیلد | توضیح |
|------|-------|
| `generateDescription` | `1` |
| `blogTitles` | عناوین پست‌ها |
| `lang` | `get_locale()` |
| `model` | همیشه `gemini` (hardcoded) |

---

## ۹. مدل‌های AI

| نام UI | مقدار API | فایل تعریف |
|--------|-----------|------------|
| GPT-5 | `gpt` | `src/Base.php` → `MODELS` |
| Gemini | `gemini` | `src/Base.php` → `MODELS` |

---

## ۱۰. WordPress Options

| Option Key | کاربرد |
|------------|--------|
| `niklink_seo_api_key` | کلید API |
| `sonic-site-description` | توضیح سایت (ورودی پرامت Assistant) |
| `sonic-client-seo-plugin` | `none` / `yoast` / `rank_math` / `all_in_one` |
| `sonic_settings` | نمایش hint تنظیمات |
| `sonic_content_create` | نمایش hint ساخت محتوا |
| `sonic_keywords` | نمایش hint Assistant |
| `sonic_posts` | نمایش hint پست‌ها |
| `sonic_do_activation_redirect` | redirect بعد از فعال‌سازی |

### Post Meta

| Meta Key | مقادیر | کاربرد |
|----------|--------|--------|
| `sonic_post_update` | `processing` / `updated` / `canceled-remote` / `` | وضعیت تولید محتوا |

---

## ۱۱. ثابت‌های توسعه (wp-config.php)

| ثابت | کاربرد |
|------|--------|
| `GHOST` | `true` → API به `http://dev.me` |
| `SOINC_DIRECT_REQUEST` | `true` → برگرداندن `$response_body` خام به جای پیام موفقیت |

---

## ۱۲. محدودیت لوکال

پلاگین `origin = home_url()` می‌فرستد. سرور Niklink callback را به این آدرس POST می‌کند.

روی `localhost` callback نمی‌رسد → پست در `processing` می‌ماند.

**راه‌حل:** ngrok / staging / override کردن `origin`

---

## ۱۳. Placeholder تصاویر داخل مقاله

سرور می‌تواند در HTML از placeholder استفاده کند:

```
[sonic_image id="1"]
```

`PostPublisher::replace_article_images()` این placeholderها را با تصاویر واقعی جایگزین می‌کند.

---

## ۱۴. فایل CSV مرتبط

جزئیات فیلدها در قالب جدول: [`sonic-prompt-fields.csv`](./sonic-prompt-fields.csv)
