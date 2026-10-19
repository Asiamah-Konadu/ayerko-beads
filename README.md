# AYERKO BEADS • Madina, Accra, Ghana

A luxury e-commerce platform and administrative atelier console for **Ayerko Beads**, handcrafted in **Madina, Accra, Ghana**.

---

## 👑 Business Identity
- **Business Name**: Ayerko Beads
- **Location**: Madina, Accra, Ghana
- **Contact / WhatsApp**: `0243825825` (`+233243825825`)
- **Social Media**: `@ayerkobeads` on Facebook, Instagram, and TikTok
- **Specialties**: Authentic Ghanaian Krobo recycled powdered glass statements, royal bridal corals, luxury crystal waist beads, and royal monarch regalia.

---

## 🌟 Key Features

### 1. Ghanaian Cedi (GHS GH₵) & Multi-Currency Engine
- **Primary Currency**: Ghanaian Cedi (`GHS` - `GH₵`)
- **Supported Global Currencies**:
  - `GHS (GH₵)` - Ghana (Default)
  - `USD ($)` - United States / Global
  - `NGN (₦)` - Nigeria
  - `GBP (£)` - United Kingdom
  - `EUR (€)` - Europe
- Currency rates can be updated anytime in the admin settings.

### 2. Live Order Tracking (`track.php`)
- Every client receives a unique royal order reference (e.g. `AYK-2610-8A4C`).
- Real-time visual progress tracker:
  1. **Order Placed / Booked**
  2. **Handcrafting & Custom Sizing in Madina Atelier**
  3. **Quality Check & Dispatch (Courier / Rider)**
  4. **Delivered & Adorned**
- Clients can track anytime by entering their **Order Number** or **Mobile Phone Number** (e.g. `0243825825`).
- Displays courier name, waybill/rider reference, items ordered, and 1-click WhatsApp support.

### 3. Direct WhatsApp Concierge & Custom Sizing
- Direct 1-click WhatsApp button configured with Ayerko Beads line (`0243825825`).
- Auto-generates tailored inquiry messages containing product name, ID, price in GHS, and waist/neck sizing questions.
- Custom sizing input for waistline measurements (inches), neck circumference, and wedding dates.

### 4. Admin Atelier Console (`/admin`)
- **Secure Authentication**: Master access password (Default: `admin123`).
- **Dashboard**: Gross orders value, active inventory counter, and low-stock alerts.
- **Product Manager**: Upload new bead creations with photos, descriptions, sacred materials, and prices.
- **Orders & Tracking Dispatch**:
  - Update status in real time (`New`, `Processing`, `Shipped`, `Completed`).
  - Assign courier service (e.g. `Accra Bolt Rider`, `VIP Parcel Office`, `DHL Express`) and waybill codes.
  - Direct 1-click WhatsApp button to chat with the client about their specific order.
- **Store Configuration**:
  - Update WhatsApp & contact phone numbers.
  - Set exchange rates for GHS, NGN, GBP, EUR.
  - Configure Mobile Money (MTN MoMo / Telecel Cash: `0243825825`) payment instructions.

---

## 🚀 How to Run in Laragon

1. **Option A: Laragon Apache**
   - Open Laragon and click **"Start All"**.
   - Navigate to `http://localhost/Beadssite` or `http://beadssite.test`.
   - Order Tracker: `http://localhost/Beadssite/track.php`
   - Admin Login: `http://localhost/Beadssite/admin/login.php`

2. **Option B: PHP Built-in Server**
   - In terminal, navigate to `c:\laragon\www\Beadssite` and run:
     ```powershell
     & "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" -S 127.0.0.1:8000
     ```
   - Open `http://127.0.0.1:8000`.

---

## 🔐 Default Admin Credentials
- **URL**: `http://localhost/Beadssite/admin/login.php`
- **Default Password**: `admin123`
*(Can be customized under **Admin > Store & Rates**).*
