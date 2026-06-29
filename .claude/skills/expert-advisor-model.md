# Skill: Expert Advisory Model – Phase 1 (IMCRM)

> FRD Source: Expert advisory model - Phase 1  
> ClickUp Task: https://app.clickup.com/t/86ex4h36w  
> Version: 1 | Approvers: Preet, Agatha, Hitesh | BA: Jerin

---

## Overview

IMCRM must support a new **Expert Advisory Model** for lead creation. Users with the correct role and permission can create referral or collaborative leads across LOBs. Created leads must be allocated to relevant LOB advisors. The lead generator is captured at the lead level so that managers can view referral structures, decide on incentives, and approve or reject collaborations.

This skill covers: role/permission setup, model selection UI, lead creation forms, referral logic, collaborative logic, EA Manager approval flow, email notifications, and permission-based EA allocation.

---

## Section A – IMCRM Model Creation

### A1. Roles & Permissions

- Create a new **role**: `EA_REFERRAL`
- Create a new **permission**: `EA_COLLABORATE`

### A2. Model Selection UI

- Any user with the `EA_REFERRAL` role **and** `EA_COLLABORATE` permission can create leads in IMCRM.
- A new option **"Expert Advisor Model"** must be presented as a **radio button** during lead creation.

### A3. EA Model Dropdown

- Once "Expert Advisor Model" is selected, the next control must be a **dropdown** labelled **"EA Model"** with exactly two values:
  - **Referral**
  - **Collaborative**

### A4. LOB Filter Dropdown

- Once the EA Model value is selected, the UI must show a **LOB (Line of Business) filter dropdown**.

### A5. Lead Source

- The lead source for all Expert Advisor referrals must be set to: **`EA_IMCRM`**

### A6. Auto Reallocation

- **No auto lead reallocation** for leads with source `EA_IMCRM`.
- Managers and the lead pool **are** able to manually reallocate these leads.

### A7. Advisor Counts & Reports

- All allocated EA leads must be **included** in advisor assigned counts.
- All allocated EA leads must be **excluded** from the advisor conversion report.

### A8. Buy Leads Exclusion

- EA leads must be **excluded from buy leads**.

### A9. Manager Role Restriction

- **Any user with the Manager role** will only have access to **EA Model = Referral**. They cannot choose Collaborate.

### A10. Confirm Button Mandate

- Above all other actions, there must be a mandatory **"Confirm"** button that must be clicked to create the lead. The lead is not submitted until Confirm is pressed.

---

## Section B – Lead Creation Form (Referral Model)

### B1. Generic Fields for Referral Model

Once the EA Model is selected as **Referral**, the following fields must be presented for lead creation:

- First Name / Last Name
- Email Address
- Phone Number

### B2. LOB Scope for Generic Fields

- The same three generic fields (First Name/Last Name, Email, Phone) must be available across **all LOB selections** except **General Insurance (Corpline)**.

### B3. Corpline Additional Field

- If the selected LOB is **Corpline**, an additional dropdown for **Business Type of Insurance** must be shown alongside the three generic fields.

### B4. Health LOB Additional Field

- If the selected LOB is **Health**, an additional dropdown for **Plan Type** must be shown alongside the 3 base fields (First Name/Last Name, Email, Phone).
- ⚠️ _Pending clarification with Jerin — field count to be confirmed before implementation._

### B5. Duplicate Check Logic

- Before creating a lead, IMCRM must run a **duplicate check** based on:
  - Email address
  - Phone number
  - LOB

### B6. Renewal Upload Source Exception

- If the duplicate match originates from a **renewal upload source**, do **not** create the lead if the expiry date is current.

### B7. Duplicate Found – No Lead Creation

- If a duplicate is found, **do not create the lead**.
- Instead, display a **popup** showing **who the existing advisor is**.

### B8. Duplicate Window

- Validate duplicate leads created **within 60 days** where lead status is anything **other than "Policy Booked"**.

---

## Section C – Referral Lead Creation Logic

### C1. ILA Allocation on Submission

- Once a lead is submitted from the referral creation form, it must land in IMCRM for **ILA (Intelligent Lead Allocation) allocation** for the specific LOB advisors.

### C2. ~~Corpline/Group Medical IPA Logic~~ _(Struck through – removed from scope)_

### C3. Assigned Advisor

- At the lead level, the **assigned advisor** must be the advisor allocated via ILA.

### C4. Lead Generator Field

- Create a new field at the IMCRM lead level called **"Lead Generator"**.
- The value of this field must be the **user who created the lead**.

### C5. Lead List & Export

- The lead list and export file must include these new columns:
  - **Lead Generator**
  - **EA Model**

### C6. Lead List Filters

- The lead list page must include filters for:
  - **EA Model**
  - **Lead Generator**

---

## Section D – Collaborative Lead Creation Logic

### D1. Collaborative Model Submission

- Once the EA Model is selected as **Collaborative** and the create button is clicked, the flow must proceed to the **existing lead creation form**.

### D2. Role Restriction for Collaborative Model

- Users who do **not** have the `RM_ADVISOR` role must **not** be able to choose the Collaborative model for the following LOBs:
  - Group Medical (GM)
  - Life
- _(Health is fully excluded from Collaborative — see D4. `GM_advisor` and `life_advisor` role restrictions are struck through and removed from scope.)_

### D3. ~~Car Advisor Referral-Only Rule~~ _(Struck through – removed from scope)_

### D4. LOBs Excluded from Collaborative Model (Referral-Only)

- The following LOBs are **completely excluded from the Collaborative model** and are **only applicable to Referral**:
  - Health
  - Car
  - Travel
- _(GM struck through in FRD — GM is available for Collaborative subject to D2 role restriction.)_

### D5. ILA Allocation on Submission

- Once a collaborative lead is submitted, it must land in IMCRM for **ILA allocation** to the **mapped Expert Advisors** for that LOB.

### D6. Assigned Advisor in Collaborative Model

- At the lead level, the **assigned advisor** must be the **user who created the lead**.

### D7. Expert Advisor Field

- A new field must be added at the IMCRM lead level called **"Expert Advisor"**.
- This field holds the **LOB Expert Advisor** to whom the lead was assigned in the Collaborative model.

### D8. Approve / Reject Buttons

- Place an **Approve** and a **Reject** button at the lead level, visible when lead status is **"Policy Issued"**.
- The lead must only proceed to remaining statuses if:
  - **Both** advisors approve, **or**
  - The **EA Manager** approves.

### D9. Button Visibility Condition

- The Approve/Reject buttons must appear **only** when:
  - Lead source is `EA_IMCRM`, **and**
  - EA Model is **Collaborative**

### D10. Rejection Escalation to EA Manager

- If either the EA or the Lead Assigned Advisor **rejects** the lead, it must be **highlighted in the EA Manager's list**.

---

## Section E – EA Manager Approval

### E1. EA Manager Role

- Create a new role: **`EA_Manager`**

### E2. EA Manager Lead Visibility

- Any user with the `EA_Manager` role can **filter, export, and view EA model leads** in IMCRM LOB quote lists.

### E3. Model-Based Filtering

- The EA Manager must be able to see leads filtered by EA model type.

### E4. Rejected Collaborative Leads

- For collaborative model leads where the assigned advisor **or** EA **rejects** the lead:
  - The EA Manager sees the **Model field as blank** for that lead.
  - The EA Manager can **select and approve** such leads.

### E5. Dropdown Fields

- Both the **Model** field and the **Status** field must render as **dropdowns** in the EA Manager view.

### E6. Model Change: Collaborative → Referral

- If the EA Manager changes a lead's model from **Collaborative** to **Referral**:
  - The **EA Advisor** value must be updated to the current **Assigned Advisor**.
  - The **old Assigned Advisor** must be moved to the **Lead Generator** field (per referral model rules).

### E7. Pending Rejection Counter

- The EA Manager must have access to a **pending rejection count** displayed on the IMCRM header.
- Clicking this count must open a page where the EA Manager can **approve or reject** leads by selecting the Status.

### E8. Filters on Pending Rejection Page

- The page must support filtering by:
  - Reference ID
  - Created Date
  - LOB
  - Status

### E9. Export Access

- The EA Manager must have access to **export** from this page.

---

## Section F – Email Notifications

### F1. Lead Submission Email (Referral or Collaborative)

- Once a referral or collaborative model lead is submitted:
  - Send an **email to the advisor**.
  - Keep the **lead generator** and **manager** in **CC**.
  - Use email template: [Template – Lead Submitted](https://app.clickup.com/2197982/docs/232ey-142298/232ey-356898)

### F2. Rejection Email to Managers

- Once a collaborative model lead is **rejected** by the EA or the assigned advisor:
  - Send an **email to the managers**.
  - Use email template: [Template – Lead Rejected](https://app.clickup.com/2197982/docs/232ey-142298/232ey-356918)

### F3. EA Manager Approval Email

- When a lead was rejected (or not approved) by EA or advisor and the **EA Manager approves**:
  - Send an email to **both** the EA and the assigned advisor.
  - Use email template: [Template – EA Manager Approved](https://app.clickup.com/2197982/docs/232ey-142298/232ey-361018)

---

## Section G – Permission-Based EA Allocation

### G1. New Permission

- Create a new IMCRM permission: **`Assigned_ExpertAdvisor`**

### G2. Collaborative Lead Assignment

- Any advisor role that holds the `Assigned_ExpertAdvisor` permission will receive **collaborative leads via ILA**.

### G3. ILA Configuration Mapping

- Advisors with existing ILA configuration mapped across all LOBs will be assigned collaborative leads as an **Expert Advisor** based on the `Assigned_ExpertAdvisor` permission.

**Example:** User A is a Health advisor, belongs to a team mapped to the ILA pool, and has the `Assigned_ExpertAdvisor` permission → User A will receive collaborative model leads as the Expert Advisor for Health LOB.

---

## Key Business Rules Summary

| Rule                      | Detail                                                                                                     |
| ------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Lead Source               | Always `EA_IMCRM` for EA model leads                                                                       |
| Auto Reallocation         | Disabled for `EA_IMCRM`; manual reallocation by manager/lead pool allowed                                  |
| Conversion Report         | EA leads excluded                                                                                          |
| Buy Leads                 | EA leads excluded                                                                                          |
| Manager Role              | Can only choose Referral (not Collaborate)                                                                 |
| Confirm Button            | Mandatory before lead creation                                                                             |
| Duplicate Window          | 60 days; excludes "Policy Booked" status                                                                   |
| Collaborative Approval    | Requires both advisors OR EA Manager approval to advance status                                            |
| Approve/Reject Visibility | Only when source = `EA_IMCRM` AND model = Collaborative AND status = Policy Issued                         |
| Model Downgrade           | Collaborative → Referral: EA Advisor becomes Assigned Advisor; old Assigned Advisor becomes Lead Generator |

---

## Roles & Permissions Checklist

| Name                     | Type       | Purpose                                                        |
| ------------------------ | ---------- | -------------------------------------------------------------- |
| `EA_REFERRAL`            | Role       | Grants access to create EA model leads                         |
| `EA_COLLABORATE`         | Permission | Required (with `EA_REFERRAL`) to see/use Collaborate model     |
| `EA_Manager`             | Role       | View, filter, export, approve/reject all EA leads              |
| `Assigned_ExpertAdvisor` | Permission | Advisor receives collaborative leads as Expert Advisor via ILA |

---

## New Lead-Level Fields

| Field Name     | Model         | Description                         |
| -------------- | ------------- | ----------------------------------- |
| Lead Generator | Referral      | User who created the lead           |
| Expert Advisor | Collaborative | LOB Expert Advisor assigned via ILA |
| EA Model       | Both          | Referral or Collaborative           |

---

## LOB Compatibility Matrix

| LOB                          | Referral                      | Collaborative            |
| ---------------------------- | ----------------------------- | ------------------------ |
| Life                         | ✅                            | ✅ requires `RM_ADVISOR` |
| Health                       | ✅                            | ❌ completely excluded   |
| Car                          | ✅                            | ❌ completely excluded   |
| Travel                       | ✅                            | ❌ completely excluded   |
| Corpline (General Insurance) | ✅ (+ Business Type dropdown) | ✅                       |
| Group Medical (GM)           | ✅                            | ✅ requires `RM_ADVISOR` |
