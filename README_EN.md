[فارسی](README.md)

# Akaunting Jalali Date Module

This module provides full Jalali (Persian) calendar support for Akaunting 3.1.x. It seamlessly integrates with Akaunting's core and converts all dates on the client-side (browser).

<p align="center"><img src="https://aramisteam.com/akaunting-git.jpg" /></p>

## ⚠️ Upgrading from Version 1 to Version 2

**Important:** Version 2 has breaking changes and is not backward compatible. Users must completely remove the old version and then install the new version.

### Major Changes in Version 2:

- **Completely changed logic:** In the previous version, date conversions were performed server-side. In the new version, all conversions happen only on the client-side (browser).
- **Advanced settings system:** Added ability to enable/disable Jalali date display and date picker.
- **Dual calendar picker:** Option to choose between Gregorian and Jalali calendars with a user-friendly switcher.
- **Persian invoice template:** Dedicated invoice template with customizable fields.
- **Persian fonts:** Vazirmatn font added for proper Persian text display.

## Features

- **Dual Date Picker:** Option to choose between Gregorian and Jalali calendars with a user-friendly switcher in all date fields.
- **Client-side Conversion:** All date conversions happen only in the user's browser, without changing data on the server.
- **Advanced Settings System:** Full control over date display and pickers through the settings panel.
- **Persian Invoice Template:** Dedicated invoice template with customizable fields and Persian fonts.
- **Vazirmatn Font:** Uses Vazirmatn font for proper display of Persian text and numbers.
- **Full Compatibility:** Works without modifying Akaunting core files.

## Libraries Used

This module is built upon the following libraries:

- **Backend:** [morilog/jalali](https://github.com/morilog/jalali) - For date conversions in PHP (only for server-side formatting).
- **Frontend:**
  - [jalaali-js](https://github.com/jalaali/jalaali-js) - For Jalali date conversions in JavaScript.
  - [jalalidatepicker](https://github.com/jalalidatepicker/jalalidatepicker) - For the Jalali datepicker UI.
- **Font:** [Vazirmatn](https://github.com/rastikerdar/vazirmatn) - Free Persian Vazirmatn font.

## Installation

### ⚠️ Important Upgrade Notes:

If you have version 1 installed, first completely remove it:

```bash
# Uninstall the module
php artisan module:uninstall JalaliDate 1
# Or delete module files from modules directory
rm -rf <your-akaunting-root>/modules/JalaliDate
```

### Installation Steps:

1. **Download the Module:**
   You have three options to get the module files:

- **Option A (Recommended):** Download the latest stable version from the [**GitHub Releases**](https://github.com/Abdipour/akaunting-jalali-date/releases) page.
- **Option B (Direct Download):** Download the latest development version directly as a [**ZIP file**](https://github.com/Abdipour/akaunting-jalali-date/archive/refs/heads/main.zip).
- **Option C (Clone):** Clone the repository into your Akaunting `modules` directory.

The final path should look like this:

```bash
<your-akaunting-root>/modules/JalaliDate/
```

2. **Install Dependencies:**
   Navigate to the module's directory in your terminal and install the required Composer and NPM dependencies.

```bash
cd <your-akaunting-root>/modules/JalaliDate
composer install
```

3. **Clear Caches:**
   To ensure all changes are applied correctly, run the following commands from the root of your Akaunting installation:

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

4. **Activation and Settings:**

- Activate the module.

```bash
php artisan module:install JalaliDate 1
```

- Go to module settings from Akaunting admin panel and configure the options.

## Usage

### Module Settings:

After activation, go to module settings and configure the following options:

- **Convert dates to Jalali:** Enable Jalali date display across the application.
- **Jalali datepicker default:** Enable Jalali date picker as the default.
- **Invoice fields:** Select fields to display in the Persian invoice template.

### Date Picker:

In date fields, users can choose between Gregorian and Jalali calendars using the "شمسی/میلادی" (Jalali/Gregorian) switcher.

### Persian Invoice Template:

When printing invoices, the "فارسی" (Persian) option is available for using the dedicated Persian template with Vazirmatn font.

## Compatibility

This module is tested for **Akaunting version 3.1.20**.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
