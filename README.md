# Akaunting Jalali Date Module

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://akaunting.com/apps/jalali-date)
[![MIT License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)

This module provides full Jalali (Persian) calendar support for Akaunting 3.1.x. It seamlessly integrates with Akaunting's core, converting all dates for display and ensuring that dates are correctly saved and processed in the backend.

## Features

- **Jalali Datepicker:** Replaces the default Gregorian datepicker with a user-friendly Jalali calendar in all date fields.
- **Automatic Date Conversion:** Automatically converts all Gregorian dates from the database to Jalali dates for display across the entire application (invoices, bills, reports, etc.) without requiring any changes to core view files.
- **Correct Date Saving:** Converts Jalali dates entered by the user back to Gregorian before saving to the database, ensuring data integrity and compatibility with Akaunting's core.
- **Preserves Core Functionality:** Works on top of Akaunting's existing date handling, preserving all original functionality and calculations.

## Libraries Used

This module is built upon two key libraries:

- **Backend:** [morilog/jalali](https://github.com/morilog/jalali) - For robust and reliable Jalali to Gregorian (and vice-versa) date conversions in PHP.
- **Frontend:** [flatpickr-jalali-support](https://www.npmjs.com/package/flatpickr-jalali-support) - For the beautiful and functional Jalali datepicker UI.

## Installation

Follow these steps to install the module in your Akaunting instance:

1.  **Download the Module:**
    You have three options to get the module files:

    - **Option A (Recommended):** Download the latest stable version from the [**GitHub Releases**](https://github.com/Abdipour/JalaliDate/releases) page.
    - **Option B (Direct Download):** Download the latest development version directly as a [**ZIP file**](https://github.com/Abdipour/JalaliDate/archive/refs/heads/main.zip).
    - **Option C (Clone):** Clone the repository into your Akaunting `modules` directory.

    The final path should look like this:

    ```bash
    <your-akaunting-root>/modules/JalaliDate/
    ```

2.  **Install Dependencies:**
    Navigate to the module's directory in your terminal and install the required Composer and NPM dependencies.

    ```bash
    cd <your-akaunting-root>/modules/JalaliDate
    composer install
    npm install
    ```

3.  **Compile Frontend Assets:**
    Build the necessary JavaScript and CSS files for the module.

    ```bash
    npm run dev
    ```

    _For production environments, use `npm run prod`._

4.  **Clear Caches:**
    To ensure all changes are applied correctly, run the following commands from the root of your Akaunting installation:
    ```bash
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear
    ```

## Usage

Once installed and enabled, the module works automatically. All date fields in the Akaunting interface will use the Jalali calendar, and all dates will be displayed in the Jalali format. The conversion process is seamless and requires no extra configuration.

## Compatibility

This module is designed and tested for **Akaunting version 3.1.x**.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
