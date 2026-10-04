# Bangladesh Angels Network (BAN) - REST API Documentation

This documentation covers all REST API endpoints created for the Bangladesh Angels Network platform, specifically architected and optimized for the **React Native Mobile Application** (iOS & Android) and other decoupled frontends.

---

## 1. General Principles

### Base URL
- **Local Development**: `http://bangladsh-angels-v2.test/api` or `http://10.0.2.2:8000/api` (Android Emulator) / `http://localhost:8000/api` (iOS Simulator)
- **Production**: `https://bdangels.co/api` (or configured production domain)

### Headers
Every API request from React Native should include:
```http
Accept: application/json
Content-Type: application/json
```
For authenticated requests, include:
```http
Authorization: Bearer <TOKEN>
```
For file uploads (pitch decks, proof of payment, profile photos), use `multipart/form-data` with `Accept: application/json`.

### Unified Response Format
All responses adhere to a consistent JSON envelope:

#### Successful Response:
```json
{
  "success": true,
  "message": "Optional descriptive message",
  "data": { ... }
}
```

#### Error Response:
```json
{
  "success": false,
  "message": "Invalid credentials.",
  "errors": {
    "email": ["These credentials do not match our records."]
  }
}
```

### Common HTTP Status Codes
- `200 OK`: Request succeeded.
- `201 Created`: Resource successfully created.
- `400 Bad Request`: Business logic constraint violation (e.g., trying to buy a plan when already active).
- `401 Unauthorized`: Unauthenticated or expired Sanctum Bearer token.
- `403 Forbidden`: Authenticated, but user lacks necessary permissions (e.g., pending approval, free tier trying to view paywalled deals, non-admin).
- `404 Not Found`: Resource does not exist.
- `422 Unprocessable Content`: Validation failure with details in `errors` map.
- `429 Too Many Requests`: Rate limit reached.
- `500 / 502 Server Error`: Upstream or server-side failure.

---

## 2. Authentication (`/api/auth`)

### 2.1 Register
- **Endpoint**: `POST /api/auth/register`
- **Auth**: Public
- **Request Body**:
  ```json
  {
    "name": "Rahim Chowdhury",
    "email": "rahim@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "phone": "+8801700000000",
    "gender": "male", // male | female | other
    "company_name": "Acme Corp", // optional
    "designation": "Director", // optional
    "investment_expertise": "beginner", // optional: beginner | intermediate | expert
    "primary_country": "Bangladesh", // optional
    "address": "Dhaka" // optional
  }
  ```
- **Response `201`**:
  ```json
  {
    "success": true,
    "message": "Registration successful.",
    "data": {
      "user": {
        "id": 1,
        "name": "Rahim Chowdhury",
        "email": "rahim@example.com",
        "role": "investor",
        "account_status": "free",
        "is_approved": false,
        "is_email_verified": false
      },
      "token": "1|sanctum_personal_access_token..."
    }
  }
  ```

### 2.2 Login
- **Endpoint**: `POST /api/auth/login`
- **Auth**: Public
- **Request Body**:
  ```json
  {
    "email": "rahim@example.com",
    "password": "Password123!",
    "device_name": "iPhone 15 Pro" // optional
  }
  ```
- **Response `200`**:
  ```json
  {
    "success": true,
    "message": "Login successful.",
    "data": {
      "user": {
        "id": 1,
        "name": "Rahim Chowdhury",
        "email": "rahim@example.com",
        "role": "investor",
        "account_status": "core",
        "is_approved": true,
        "is_email_verified": true
      },
      "token": "2|sanctum_personal_access_token..."
    }
  }
  ```

### 2.3 Current User / Profile
- **Endpoint**: `GET /api/auth/me` or `GET /api/user`
- **Auth**: `auth:sanctum`
- **Response `200`**:
  ```json
  {
    "success": true,
    "data": {
      "user": {
        "id": 1,
        "name": "Rahim Chowdhury",
        "email": "rahim@example.com",
        "role": "investor",
        "account_status": "core",
        "payment_status": "paid",
        "is_approved": true,
        "profile_photo_url": "https://...",
        "phone": "+8801700000000",
        "company_name": "Acme Corp",
        "designation": "Director",
        "gender": "male",
        "investment_expertise": "expert"
      }
    }
  }
  ```

### 2.4 Update Profile
- **Endpoint**: `PUT /api/auth/profile`
- **Auth**: `auth:sanctum`
- **Request Body**:
  ```json
  {
    "name": "Rahim Chowdhury Updated",
    "email": "rahim@example.com",
    "phone": "+8801700000001",
    "address": "Gulshan 2, Dhaka",
    "gender": "male",
    "company_name": "Acme Ventures",
    "designation": "Managing Director",
    "investment_expertise": "expert",
    "primary_country": "Bangladesh",
    "linkedin": "https://linkedin.com/in/rahim"
  }
  ```

### 2.5 Update Password
- **Endpoint**: `PUT /api/auth/password`
- **Auth**: `auth:sanctum`
- **Request Body**:
  ```json
  {
    "current_password": "OldPassword123!",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
  }
  ```

### 2.6 Forgot & Reset Password
- **Forgot Password**: `POST /api/auth/forgot-password`
  - Body: `{ "email": "user@example.com" }`
- **Reset Password**: `POST /api/auth/reset-password`
  - Body: `{ "token": "...", "email": "...", "password": "...", "password_confirmation": "..." }`

### 2.7 Logout
- **Endpoint**: `POST /api/auth/logout`
- **Auth**: `auth:sanctum`
- Revokes current device's access token.

---

## 3. Public Content & Discovery

### 3.1 Unified Home Bundle
- **Endpoint**: `GET /api/home`
- **Auth**: Public
- **Description**: Consolidates homepage data into a single payload for blazing-fast mobile rendering (portfolio deals, team members, FAQs, stats, programs, events, membership tiers).

### 3.2 Membership Plans
- **Endpoint**: `GET /api/plans`
- **Auth**: Public
- Returns all active membership tiers (`free`, `core`, `advanced`, `institutional`), their pricing, feature breakdown, and current policy version.

### 3.3 FAQ
- **Endpoint**: `GET /api/faq`
- **Auth**: Public
- Returns FAQ categorized items.

### 3.4 CMS Cards & Content
- `GET /api/team`: Leadership and team members.
- `GET /api/team-about-section`: BAN About Us highlight copy and badges.
- `GET /api/services`: Startup services and advisory cards.
- `GET /api/what-we-do-cards`: Core pillars and initiatives.
- `GET /api/landing-program-cards`: Programs (Angel Academy, Syndicates, etc.).
- `GET /api/landing-page-stats`: High-level ecosystem statistics.
- `GET /api/resource-hub-cards`: Knowledge hub cards.
- `GET /api/portfolio`: Full investment portfolio list.
- `GET /api/homepage-portfolios`: Featured portfolio showcase.
- `GET /api/our-investors`: Notable investors and partner quotes.
- `GET /api/policies`: List of legal policies (`terms-and-conditions`, `privacy-policy`, `delivery-policy`, etc.).
- `GET /api/policies/{policy}`: Full policy details and content.

### 3.5 Blogs
- **Endpoint**: `GET /api/blogs`
  - **Query Params**: `search=...`, `category=...`, `per_page=12`, `page=1`
- **Endpoint**: `GET /api/blogs/{slug}`: Single blog detail.

### 3.6 Publications & Reports
- **Endpoint**: `GET /api/publications`
  - **Query Params**: `category=...`, `per_page=12`
- **Endpoint**: `GET /api/publications/{id}`
- **Endpoint**: `GET /api/publications/{id}/download`: Streams binary PDF for in-app viewing or saving to device storage.

### 3.7 Resources & Events
- **Endpoint**: `GET /api/resources`
  - **Query Params**: `type=event|webinar|deckvue`
- **Endpoint**: `GET /api/resources/{slug}`
- **Endpoint**: `GET /api/deckvue`: DeckVue resources overview.

---

## 4. Public Submissions & Inquiries

### 4.1 Founder Pitch Deck
- **Endpoint**: `POST /api/pitch` (alias: `POST /api/startups/pitch`)
- **Headers**: `Content-Type: multipart/form-data`
- **Payload**:
  - `contact_email` (string, email, required)
  - `one_line` (string, max 280, required)
  - `pitch_deck` (file, PDF, max 12MB, required)
- **Response `201`**: `{ "success": true, "message": "Pitch deck submitted successfully..." }`

### 4.2 Angel Academy Application
- **Get Form Options**: `GET /api/angel-academy/options`
  - Returns possible values for `invested_before`, `primary_motivation`, `startup_stages`, `sectors`, and `cheque_size`.
- **Submit Application**: `POST /api/angel-academy/apply`
  - **Payload**:
    ```json
    {
      "name": "Investor Candidate",
      "email": "candidate@example.com",
      "contact_number": "+8801700000000",
      "linkedin_url": "https://linkedin.com/in/candidate",
      "invested_before": "yes_1_2",
      "primary_motivation": "learn_angel_investing",
      "startup_stages": ["pre_seed", "seed"],
      "sectors": ["fintech", "logistics"],
      "cheque_size": "5k_25k"
    }
    ```

### 4.3 Member Application
- **Endpoint**: `POST /api/member/apply`
- **Payload**:
  ```json
  {
    "first_name": "Tanvir",
    "last_name": "Ahmed",
    "address": "Gulshan 1, Dhaka",
    "email": "tanvir@example.com",
    "phone": "1711223344",
    "country_code": "+880",
    "gender": "male",
    "primary_country": "Bangladesh",
    "organization": "InvestCorp",
    "designation": "Partner",
    "investment_expertise": "intermediate",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "selected_plan": "core" // or "free", "advanced", "institutional"
  }
  ```

---

## 5. Checkout & Membership Payments

### 5.1 Initiate Membership Upgrade
- **Endpoint**: `POST /api/checkout`
- **Auth**: Optional (can be called by a guest or an authenticated user)
- **Payload**:
  ```json
  {
    "name": "Tanvir Ahmed",
    "email": "tanvir@example.com",
    "address": "Gulshan 1, Dhaka",
    "phone": "1711223344",
    "country_code": "+880",
    "primary_country": "Bangladesh",
    "company_name": "InvestCorp",
    "designation": "Partner",
    "gender": "male",
    "investment_expertise": "intermediate",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "plan": "core",
    "price": 399.00,
    "policy_consent": 1,
    "policy_version": "2026-v1"
  }
  ```
- **Response `200`**:
  ```json
  {
    "success": true,
    "data": {
      "status": "pending_payment", // or "completed" in test mode
      "payment_url": "https://securepay.aamarpay.com/request.php?...",
      "transaction_id": "BAN1712345678",
      "order_reference": "ORD-2026-...",
      "token": "3|sanctum_token...",
      "user": { ... }
    }
  }
  ```
- **Mobile Integration**: Open `payment_url` in a React Native Webview (`react-native-webview`) or device browser. Listen for redirect to `/checkout/finish` or poll `/api/checkout/status/{order_reference}`.

### 5.2 Check Order Status
- **Endpoint**: `GET /api/checkout/status/{reference}`
- Returns payment status, delivery status, and plan snapshot.

---

## 6. Investor Portal (`auth:sanctum` & `approved`)

### 6.1 Investor Dashboard
- **Endpoint**: `GET /api/investor/dashboard`
- Returns portfolio summary, chart datapoints, and startup sector allocation.

### 6.2 Deal Flow (Active Deals)
- **List Deals**: `GET /api/deals`
  - Paywalled: Returns `403` if user has `free` membership or is unapproved.
- **Deal Details**: `GET /api/deals/{slug}`
  - Returns complete deal thesis, financials, deck, and investor interest count.
- **Express Interest**: `POST /api/deals/{deal_id}/invest`
  - Body: `{ "type": "invest" }`
- **Make Commit**: `POST /api/deals/{deal_id}/commit`
  - Body: `{ "amount": 15000, "deadline": "2026-12-31" }`
- **Formal Commit Submission**: `POST /api/deals/{deal_id}/commit-submission`
  - Body: `{ "name": "Tanvir", "email": "tanvir@example.com", "whatsapp_number": "+880...", "amount": 15000, "currency": "USD" }`

### 6.3 Investor Investments & Commits History
- `GET /api/investor/investments`: List of finalized investments.
- `GET /api/investor/commits`: List of submitted soft/formal commits.

### 6.4 Membership Orders & Deliveries
- `GET /api/membership/orders`: All user membership orders and invoices.
- `GET /api/membership/orders/{id}`: Detailed invoice and fulfillment breakdown.
- `GET /api/membership/orders/{id}/policies`: Snapshot of agreed terms at purchase time.
- `POST /api/membership/orders/{id}/confirm-delivery`: Mark services delivered.

### 6.5 BAN Wealth (Syndicate Funds)
- `GET /api/ban-wealth/funds`: Available funds and allocation opportunities.
- `POST /api/ban-wealth/orders`: Submit wealth allocation request (`fund_name`, `amount`, `payment_method`, `proof_file`).

---

## 7. Admin Endpoints (`auth:sanctum` + `admin` middleware)

The platform provides a complete administration suite under `/api/admin/*`:

| Module | Method & Route | Description |
|---|---|---|
| **Dashboard** | `GET /api/admin/dashboard` | Ecosystem metrics, deal count, user counts, charts |
| **Members** | `GET /api/admin/members` | Filterable, searchable member roster |
| | `POST /api/admin/members` | Create new member |
| | `GET /api/admin/members/{id}` | Member profile & investments |
| | `PUT /api/admin/members/{id}` | Update member profile |
| | `POST /api/admin/members/{id}/approve` | Approve investor account |
| | `POST /api/admin/members/{id}/verify-email` | Manually mark email verified |
| | `PATCH /api/admin/members/{id}/status` | Change membership tier (`core`, `advanced`, etc.) |
| | `DELETE /api/admin/members/{id}` | Delete member |
| **Deals** | `GET /api/admin/deals` | List all deals |
| | `POST /api/admin/deals` | Create new deal |
| | `GET /api/admin/deals/{id}` | Show deal with pitch details |
| | `PUT /api/admin/deals/{id}` | Update deal |
| | `DELETE /api/admin/deals/{id}` | Delete deal |
| | `GET /api/admin/deals/{id}/member-activity` | Investor interest & commitments on deal |
| | `POST /api/admin/deals/{id}/member-activity/investments/{inv_id}/toggle-viewed` | Mark viewed |
| | `POST /api/admin/deals/{id}/member-activity/mark-all-viewed` | Bulk mark all viewed |
| **Investor Investments** | `GET /api/admin/investor-investments` | Completed portfolio investments |
| | `POST /api/admin/investor-investments` | Record completed investment |
| | `GET /api/admin/investor-investments/suggestions` | Typeahead autocomplete for companies/investors |
| | `PUT /api/admin/investor-investments/{id}` | Update investment record |
| | `DELETE /api/admin/investor-investments/{id}` | Delete investment record |
| **Subscriptions** | `GET /api/admin/subscriptions` | Membership subscriptions list |
| | `POST /api/admin/subscriptions/tiers` | Update tier yearly pricing and active status |
| **Orders & Evidence** | `GET /api/admin/membership-orders` | Membership purchase orders |
| | `POST /api/admin/membership-orders/{id}/records` | Upload service delivery proof document |
| | `GET /api/admin/membership-orders/records/{id}/download` | Download delivery proof file |
| **BAN Wealth Orders** | `GET /api/admin/ban-wealth-orders` | Wealth allocation requests |
| | `PATCH /api/admin/ban-wealth-orders/{id}` | Approve or update order status |
| **Notifications** | `GET /api/admin/notifications` | Activity logs (express interest, commits) |
| | `GET /api/admin/notifications/unread-count` | Real-time badge counter |
| | `POST /api/admin/notifications/mark-all-viewed` | Clear unread notifications |
| **Submissions** | `GET /api/admin/founder-pitches` | Founder pitch submissions |
| | `GET /api/admin/founder-pitches/{id}/deck` | Download founder pitch deck PDF |
| | `GET /api/admin/angel-academy-applications` | Angel Academy candidate applications |
| **CMS Blogs** | `GET/POST/PUT/DELETE /api/admin/blogs` | Full blog editorial lifecycle & status toggle |
| **CMS Publications** | `GET/POST/PUT/DELETE /api/admin/publications` | Publications & research report management with PDF upload |
| **CMS Events** | `GET/POST/PUT/DELETE /api/admin/events` | Events & webinars management |
| **CMS Cards** | `/api/admin/homepage-portfolios` | Homepage featured portfolio ordering |
| | `/api/admin/resource-hub-cards` | Resource hub cards |
| | `/api/admin/startup-services` | Startup advisory services |
| | `/api/admin/what-we-do-cards` | What We Do cards |
| | `/api/admin/landing-page-stats` | Landing metrics |
| | `/api/admin/landing-program-cards` | Landing program cards |
| | `/api/admin/team-about-section` | Team about highlight copy |
| | `/api/admin/team-members` | Team leadership members |
| **Broadcasts** | `GET /api/admin/mail-list` | Recipient directory and campaign send history |
| | `POST /api/admin/mail-list/send` | Send broadcast email campaign |

---

## 8. React Native Client Best Practices

### Recommended Axios Configuration
```typescript
import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';

const apiClient = axios.create({
  baseURL: 'https://bdangels.co/api', // or your dev IP
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15000,
});

// Automatic token attachment
apiClient.interceptors.request.use(async (config) => {
  const token = await AsyncStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Centralized error handling
apiClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      // Redirect to Login screen & clear token
      AsyncStorage.removeItem('auth_token');
    }
    return Promise.reject(error.response?.data || error);
  }
);

export default apiClient;
```

### File Uploading Example (Pitch Deck)
```typescript
import DocumentPicker from 'react-native-document-picker';

const uploadPitchDeck = async () => {
  const res = await DocumentPicker.pickSingle({
    type: [DocumentPicker.types.pdf],
  });

  const formData = new FormData();
  formData.append('contact_email', 'founder@startup.io');
  formData.append('one_line', 'AI Platform for Bangladesh logistics');
  formData.append('pitch_deck', {
    uri: res.uri,
    type: res.type || 'application/pdf',
    name: res.name || 'pitch_deck.pdf',
  } as any);

  return apiClient.post('/pitch', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
};
```
