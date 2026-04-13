# BRD: Health Renewals CQF and OCB

| **Request Date**          |                                                                                      |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/867809tcz](https://app.clickup.com/t/867809tcz)) |     |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   | Mahesh; Agatha                                                                       |     |
| **Approvers**             | Avinash                                                                              |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   | Hussain                                                                              |     |
| **CDTO**                  | Paula                                                                                |     |
| **CMO**                   | Hitesh                                                                               |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       |                                                                                      |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

## **Background**

The current process for managing health renewals is manual, labour-intensive, and time-consuming. It requires extensive manual input and monitoring, increasing the potential for errors and delays that could impact customer satisfaction and operational efficiency. This situation underscores the need for discussing improvements or implementing automated systems to streamline the renewal process.

##

## **Main Story**

The introduction of the Health CQF flow aims to automate the process, significantly reducing manual labour and time consumption. This development is a major advancement in streamlining operations and enhancing efficiency.

## **Objective**

Objective for developing Health CQF:

- Develop SOP for advisor allocation
- Addition of payment link
- OCB Mail
- Customer journey for renewing a plan with renewal premium & payment link available
- Customer journey for renewing a plan without renewal premium & payment link available
- Flag renewal clients as duplicates and send the request to the renewal advisors

## **Business Requirements**

| **User story**    | **Description**              | **Priority** | **Requires FR** | **Task/FR link**                                                                                                                                                                    |
| ----------------- | ---------------------------- | ------------ | --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| As IM Management  | <br>                         | Must Have    | Yes             | FR link for payment link: Private ([https://app.clickup.com/t/86epeqxtc](https://app.clickup.com/t/86epeqxtc))<br>                                                                  |
| As Health Manager | <br>                         | Must have    |                 |                                                                                                                                                                                     |
| As CQF Manager    | <br><br><br><br><br><br><br> | Must have    | Yes             | SOP for manual renewal adviosr allocation: Private ([https://app.clickup.com/2197982/docs/232ey-48978/232ey-155938](https://app.clickup.com/2197982/docs/232ey-48978/232ey-155938)) |
| As CQF team       | <br><br><br><br>             | Must have    | Yes             |                                                                                                                                                                                     |
| As Customer       | <br><br><br>                 | Must have    | Yes             |                                                                                                                                                                                     |
| As Advisor        | <br><br><br><br><br>         | Must have    | Yes             |                                                                                                                                                                                     |
| As Management     | <br><br>                     | Should have  | Yes - Later     | Private ([https://app.clickup.com/t/86enpye94](https://app.clickup.com/t/86enpye94))<br>Private ([https://app.clickup.com/t/86epnv1th](https://app.clickup.com/t/86epnv1th))        |

##

## **Open Questions:**

| **No.** | **Question/Concern**                                                                                                                                                           | **Raised by** | **Status** | **Outcome**                                                                                                                                                                                 |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------- | ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1       | Loading of premiums in cases where premium is not available.                                                                                                                   |               | Rejected   | Implementation of PUA has been discarded                                                                                                                                                    |
| 2       | Constraints, where currently insured with renewal plans show up with zero premiums, have to be removed.                                                                        | Agatha        | Closed     | Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-191998](https://app.clickup.com/2197982/docs/232ey-46718/232ey-191998))                                                    |
| 3       | TE & DIC have agreed to share renewal premiums 45 days prior to expiry, while others are still at 30 days. This is a concern given that OCBs are to be triggered 30 days prior | Alina         | Closed     | For TE & DIC, send OCBs with renewal premium and payment link.<br><br>For other insurers - in OCB, have renew now button, ask for upload of docs, then show TYP along with advisor details  |
| 4       | In case of TE & DIC, when to ask customers to upload documents for renewals.                                                                                                   | Agatha        |            | TE should either remove the document constraint or share the documents with us.<br>DIC - we will have to trigger a mail after payment (once customer clicks on apply now) - to ask for docs |

##

# FR: \*Display premiums for all health plans from incumbent insurers (Live)

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 14/03/2024 | 1.0         | Alina Poly      |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86eqfu4em](https://app.clickup.com/t/86eqfu4em)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   | Agatha                                                                               |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Alina                                                                                |     |
| **QA**                    |                                                                                      |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            |                                                                                      |     |
| **To Inform**             |                                                                                      |     |

##

## **Background**

At present, IMCRM does not show the premium amounts for the plans of currently insured with insurer during the renewal process. This limits advisors from presenting upgraded or downgraded options or in switching TPAs/networks. To obtain an estimated premium, new leads must be generated, and the estimated premium has to be manually entered into the renewal reference ID. This process is time-consuming.

## **A**. **User story: Remove existing constraints for renewal plans**

As a business, I want to eliminate existing constraints on displaying premiums (for renewals) so that advisors can manage their time efficiently.

## **Requirements:**

1. All plans of the renewal insurer currently show zero premiums across all networks. This needs to be updated to display the correct premiums from CMS.

For example, if a customer is currently insured with Takaful Emarat, all their plans in IMCRM currently show zero premiums across all networks (NAS, Mednet, Nextcare, Ecare, Aafiya, etc.). This needs to be modified to display the premiums from IMCMS.

# FR: \*Upload & Update for health CQF

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments**                                     |
| ---------- | ----------- | --------------- | ------------------------------------------------ |
| 20/06/2024 | 1.0         | Alina           | \-                                               |
| 04/11/2024 | 1.1         | Alina           | Add samples for upload & update                  |
| 05/11/2024 | 1.2         | Alina           | Make modifications based on inputs from AA & AP  |
| 14/11/2024 | 1.3         | Alina           | Make modifications based on inputs from AP       |
| 20/11/2024 | 1.4         | Alina           | Make modifications based on feedback from PM     |
| 27/11/2024 | 1.5         | Alina           | Make changes based on technical feedback from HF |
| 29/11/2024 | 1.6         | Alina           | Make changes based on feedback from AP           |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86eqqxbf2](https://app.clickup.com/t/86eqqxbf2)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Alina Poly                                                                           |     |
| **QA**                    |                                                                                      |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            |                                                                                      |     |
| **To Inform**             |                                                                                      |     |

## **A. User story: IMCRM Updates**

As Renewals Manager, I want to have a separate module for non-motor upload & update so that non-motor upload can be streamlined.

## **Requirements:**

1. New module has to be introduced under "Renewals" in IMCRM, titled "Non-Motor Upload & Update."

![](https://t2197982.p.clickup-attachments.com/t2197982/e17047d8-cc00-4a21-9494-eddfe4ee2cf4/image.png)

2. Currently, we only require the upload & update functions for the Health LOB in this module, so the "Line of Business" filter can be prefilled with "Health."
3. Remaining features under this module should mirror the existing "Upload & Update" module.

Below is the sample xlsx file that should be available under the "Download Sample XLSX" button.

[https://docs.google.com/spreadsheets/d/1INbPxKJG9aEZ8Ne0MpSdcuTUnQCQ5oQOfWbsvsxX5Es/edit?gid=1062433040#gid=1062433040](https://docs.google.com/spreadsheets/d/1INbPxKJG9aEZ8Ne0MpSdcuTUnQCQ5oQOfWbsvsxX5Es/edit?gid=1062433040#gid=1062433040)

4. Notes entered in the _Upload & Update_ section should appear under the _Additional Notes_ section in IMCRM. Since these notes might include previous policy numbers, *Transaction Approved At* and *Additional Notes* fields need to be swapped in IMCRM.

[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160298?block=block-534b5a50-ff6c-4cb4-948f-2ae51932d61d)

![](https://t2197982.p.clickup-attachments.com/t2197982/f741f7e8-6ec1-4240-87b5-ddc8fc241f35/image.png)

5. Existing validation error messages for mandatory fields should be applied here (mirroring those used for motor).
6. Exporting incorrect data should be possible from IMCRM, allowing the CQF team to focus solely on updating bad data (mirroring the existing behaviour of the motor).

## **B. User story:** **Upload and Update of Renewals**

As Renewals Manager, I want to be able to update the renewal leads with additional information so that leads are modified with renewal details.

## **Requirements:**

1. Attachment below shows the data fields that are to be entered in the upload & update template and displayed in IMCRM. **_This table would be specific to Health's LOB_**_._
   [https://docs.google.com/spreadsheets/d/1-0Gn_E02217QS4wctw3fHOgxyc_tdZOPmNs-71oFBhY/edit?gid=0#gid=0](https://docs.google.com/spreadsheets/d/1-0Gn_E02217QS4wctw3fHOgxyc_tdZOPmNs-71oFBhY/edit?gid=0#gid=0)1. For family plans, if you need to enter multiple values in the same field, you can separate them using a vertical bar (\`|\`). For example, when entering member names, the format should look like this:

Alexa Webber|Ross Webber|Russell Webber|Miriam Webber

2. If renewal premium is unavailable for the specified plan & copay code, the premium displayed in IMCRM should be set to "0".
   1. Once renewal premiums are available, users should be able to enter them under "Base Price".
      ![](https://t2197982.p.clickup-attachments.com/t2197982/465c69b5-da5a-48e6-993e-335547eeea8b/image.png)
3. For the same batch, users can perform Upload and Update multiple times if required
   1. If reuploaded, the last uploaded data should be overridden with the new data.
4. Renewal banner should be displayed only against the selected renewal plan and copay - based on **plan code** & **renewal co-pay** entered during upload & update.
   1. Banner should only be displayed against the renewal plan & renewal copay. If the renewal copay is changed, the banner should not be displayed.

![](https://t2197982.p.clickup-attachments.com/t2197982/1180c145-2357-4bfb-97fc-08161c23228b/image.png)

## **C. User story: Data landing once upload & update is done**

As Business, I want the uploaded data to land in the appropriate locations so that it is easily accessible.

## **Requirements:**

1. After the CQF team completes the upload and update process, following outlines where each field from the Upload & Update document should be stored within IMCRM.

**Customer Name**, **Customer Email** & **Customer Mobile** should land within "Customer Profile"
![](https://t2197982.p.clickup-attachments.com/t2197982/3e8eb7b2-77ad-4507-90aa-d3aff00c8f55/image.png)

**Plan Name**: Under "Plan Name" within "Available Plans" with renewal banner against it.
![](https://t2197982.p.clickup-attachments.com/t2197982/35b5c2d9-4815-4967-8ea8-fc042c8c5b5a/image.png)

**Emirate of Visa**: Under "Emirate of Visa" within "Member Details"
![](https://t2197982.p.clickup-attachments.com/t2197982/8d5ad61f-d25b-4c19-8361-4e75b0cbb0f1/image.png)

**Previous Policy Premium**: Under "Previous Policy Premium" within "Last Year's Policy Details"
![](https://t2197982.p.clickup-attachments.com/t2197982/fe858c13-6913-4634-8431-97ad5850251e/image.png)

**Renewal Premium**: Under "Base Price" within "View" action. In the case of family plans, each renewal premium should reflect against base price of each member.
![](https://t2197982.p.clickup-attachments.com/t2197982/33e0cd4b-5e67-4c32-b2fa-87e10ef713c5/image.png)

**Renewal Co-Pay**: Under "Co-Pay/Co-Insurance" within "Available Plans". Renewal premiums entered would only apply to the specified plan's specified copay.
![](https://t2197982.p.clickup-attachments.com/t2197982/fc7062ed-0d84-4d65-bc66-6365ae2873df/image.png)

**DOB**: Under "DOB" within "Member Details"
![](https://t2197982.p.clickup-attachments.com/t2197982/f361d475-b88a-4e0a-92a3-c08387eb8ef0/image.png)

**Nationality**: Under "Nationality" within "Member Details"![](https://t2197982.p.clickup-attachments.com/t2197982/1eb660d8-4009-4823-8b2c-c932639a7354/image.png)

**Gender**: Under "Gender" within "Member Details"
![](https://t2197982.p.clickup-attachments.com/t2197982/84dd6a59-6d63-4010-8ba5-b18e8c230543/image.png)

**Member Category**: Under "Member Category" within "Member Details"
![](https://t2197982.p.clickup-attachments.com/t2197982/075ca64d-0126-49a8-b6e1-6525e6705e64/image.png)

**Member Name**: Under "Member Name" within "Member Details"
![](https://t2197982.p.clickup-attachments.com/t2197982/bc1977ee-f286-431d-a726-b8d8806780e8/image.png)

**Payment Link** Private ([https://app.clickup.com/2197982/docs/232ey-50818/232ey-160438](https://app.clickup.com/2197982/docs/232ey-50818/232ey-160438))

**Notes:** This should be displayed under "Additional Notes".
![](https://t2197982.p.clickup-attachments.com/t2197982/388e3540-71b8-43ca-8440-76aa20361f3f/image%20-%202024-11-29T170346.763.png)

**Previous Plan Type** and **Previous Network** should be auto-fetched based on the **plan code** entered during Upload & Update.
![](https://t2197982.p.clickup-attachments.com/t2197982/2241ac0b-fdad-43f3-96f2-ea85cd91487e/image.png)

**Salary Band** should be auto-filled based on the selected **Member Category**. (This is an existing mapping in the system)
![](https://t2197982.p.clickup-attachments.com/t2197982/69292553-1fd6-4290-9254-0c070fc26cdc/image.png)

# FR: \*Fetching of alternate plans for health renewals

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments**                                       |
| ---------- | ----------- | --------------- | -------------------------------------------------- |
| 13/12/2024 | 1.0         | Alina           | \-                                                 |
| 10/02/2025 | 1.1         | Alina           | Modified fetch conditions                          |
| 26/02/2025 | 2.0         | Alina           | Modify FR to automatically fetch alternate options |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86eqvx7rg](https://app.clickup.com/t/86eqvx7rg)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Alina Poly                                                                           |     |
| **QA**                    |                                                                                      |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            |                                                                                      |     |
| **To Inform**             |                                                                                      |     |

##

## **A. User story: Automatic fetching of alternate plans for health renewals**

As **renewals manager**, I want to automatically fetch available plans so that alternate plans can be shared with customers manually if required.

## **Requirements:**

1. After upload and update Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-160298](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160298)), alternate plans should be fetched automatically.
   1. Fetching of alternate plans should happen for **uploaded leads** that meet both the below criteria: 1. **Upload and update status:** Completed 2. **Lead status:** Allocated
      _NB: For cases that fail during upload and update, the CQF team will rework and re-upload them as new entries with new IDs. In this setup, fetching can be automated for each entry under "Uploaded Renewal Leads Files"._
      ![](https://t2197982.p.clickup-attachments.com/t2197982/dc90bd6c-1b6b-4e94-bbc8-7e723d69bfaa/image.png)
   2. Once plans are automatically fetched, it has to reflect under "Plan Processes" under Non-motor batches (this will be available a new module that will be introduced) [](https://app.clickup.com/2197982/docs/232ey-46718/232ey-197538?block=block-98c09557-2a37-4443-9105-bc81e4201adc).
      ![](https://t2197982.p.clickup-attachments.com/t2197982/fec92b29-ef28-404d-aecb-97270cdae7b2/image.png)

## **B. User story: Introduce non-motor batch module within IMCRM**

As **renewals manager**, I want to introduce a Non-Motor Batches module so that alternate plans can be fetched manually if automatic fetching fails.

## **Requirements:**

1. Introduce a new module, **Non-motor batches**, under Renewals.

![](https://t2197982.p.clickup-attachments.com/t2197982/ee40ea40-1815-47dc-b3a4-f66836576013/image.png)

2. Following filters are to be added:
   - Line of business - Dropdown with option **Health** - Mandatory field
   - Year - Dropdown - Mandatory field
   - Month - Dropdown - Mandatory field
   - Renewal Batch - Existing filter
3. When a specific year and month are selected in the filters, show all health renewal batches with policies that expire within that timeframe.
4. Remaining functionalities of the existing manual plan-fetching process should be applied to health renewals.
5. This module should only be available for users with the permission **renewals_batches_nonmotor**.

## **C. User story: Age calculation while fetching plan**

As **health** **manager**, I want to retrieve available plans based on the customer's age at renewal so that accurate premiums can be shared with customers who require alternate plans.

## **Requirements:**

1. While fetching, alternate plans should be retrieved with premiums calculated based on the member's **age at renewal**, rather than the current date or upload date. 1. For this, age of the customer **one day after the previous policy expiry date** should be considered.
   _Example Scenario\_\_:_

| **Scenario**                                          | **Upload Date** | **Previous Policy Expiry Date** | **Customer’s Date of Birth** | **Age Calculation Date** | **Customer’s Age on Calculation Date** | **Explanation**                                                                                                                                                                   |
| ----------------------------------------------------- | --------------- | ------------------------------- | ---------------------------- | ------------------------ | -------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Example 1 (Birthday After Renewal Date)**           | 13th Feb 2025   | 25th May 2025                   | 5th Aug 1990                 | 26th May 2025            | **34 years**                           | Customer's age should be calculated as of 26th May 2025 (one day after policy expiry). Since their birthday (5th Aug) is after this date, age is **34** for premium calculations. |
| **Example 2 (Birthday Between Upload & Expiry Date)** | 13th Feb 2025   | 25th May 2025                   | 10th Mar 1990                | 26th May 2025            | **35 years**                           | Customer’s age should be calculated as of 26th May 2025. Since they turned 35 on 10th March 2025 (before this date), their age is **35** for premium calculations.                |

# FR: \*Customer Journey for health renewals

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments**                             |
| ---------- | ----------- | --------------- | ---------------------------------------- |
| 24/06/2024 | 1.0         | Alina           |                                          |
| 17/11/2024 | 1.1         | Alina           | Scope change - without alternate options |
| 14/02/2025 | 1.2         | Alina           | Based on feedback after internal review  |
| 18/02/2025 | 1.3         | Alina           | Based on feedback after internal review  |
| 27/03/2025 | 1.4         | Alina           | Scope change - with alternate options    |
|            |             |                 |                                          |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86eqvx56q](https://app.clickup.com/t/86eqvx56q)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Alina Poly                                                                           |     |
| **QA**                    |                                                                                      |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            |                                                                                      |     |
| **To Inform**             |                                                                                      |     |

## **A.** **User story: Renewal OCB Mail**

_(_**_Must have_** _feature)_
As IM, I want to automatically send OCB mails for health renewals so that customers are reminded to renew their policies on time.

## **Requirements:**

1. System should automatically trigger OCB mail to the customer 30 days before policy expiry.
   1. Trigger condition: Lead source as "renewal_upload" + "Previous Policy Expiry" = 30 days from current date + Plans fetched status = "Completed"(screenshot of completed status attached below).
      ![](https://t2197982.p.clickup-attachments.com/t2197982/70f30f4f-e205-4e64-9f68-e2f9ded1a06c/image.png)
   2. If the scheduled trigger date falls on a weekend, the OCB mail should be sent on the preceding Friday.
2. OCB is sent to the policyholder’s primary email.
3. OCB should be sent from the allocated advisor's mail ID. If not available, the fallback should be [health@insurancemarket.ae](mailto:health@insurancemarket.ae).
4. Once OCB mail is sent to the customer, lead status of those leads should change to **Quoted**.
5. Three types of OCBs can be triggered depending on the availability of data.
   1. Both renewal premium and payment link available[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-9e944877-2be9-40fd-b7b6-6c88940e21a2)
   2. Only renewal premium available[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-c5bbb378-3f10-4a05-b669-1c509d2016f0)
   3. Both, renewal premium & payment link, are not available[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-3b93e62d-9df9-4f64-994b-642dfb4bd7fa)
6. Regardless of which OCB mail is triggered, as an attachment, the table of benefits (TOB from IMCMS) corresponding to the plan's specified copay should be included with the email.
7. Routing structure for the renewal OCB would be as below:

If the advisor's email ID is unavailable, the OCB mail should be triggered from the default email ID: [`health@insurancemarket.ae`](mailto:health@insurancemarket.ae).

| **Health renewal OCB email**                                     |
| ---------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| From                                                             | <Advisor Email ID> via health insurancemarket <[health@insurancemarket.ae](mailto:health@insurancemarket.ae)\> |
| To                                                               | <Client's email>                                                                                               |
| Reply to                                                         | <[instant@alfred.insurancemarket.ae](mailto:instantalfred@insurancemarket.ae)\>                                |
| CC                                                               | <[health@insurancemarket.ae](mailto:health@insurancemarket.ae)\><Advisor Email ID>                             |
| Email Subject                                                    | Renew Your Health Insurance – {Policyholder's name} before {Expiry Date}                                       |
| **Preview Text:** Act now and renew your health insurance today! |

![](https://t2197982.p.clickup-attachments.com/t2197982/eac11fc9-fb62-4eee-83a0-a0614d9902f9/image%20-%202025-06-03T120906.709.png)

## **B. User story: Customer flow if renewal premium** **and** **payment link are available**

_(_**_Must have_** _feature)_
As a customer, I want to receive an OCB email containing both the renewal premium and payment link so that I can review my plan details and proceed to pay.

## **Requirements:**

Customer flow if renewal premium & payment link are available:
![](https://t2197982.p.clickup-attachments.com/t2197982/bbacaf98-7bce-4202-9312-dcf02782ef2c/image.png)
[https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-9473&t=HHRNiX1HD78DdxyT-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-9473&t=HHRNiX1HD78DdxyT-4)

1. OCB in this case should include:
   1. Advisor & member details
   2. Insurer, network, plan name, annual limit, key hospitals & clinics, regions covered
   3. Renewal copay and renewal premium (excluding VAT)
   4. "Renew Now" button (redirecting to renewal-specific redirection page mirroring flow of NB Payment link)
   5. Alternate options hyperlink (redirecting customers to IM quote page)
   6. Renewal terms
   7. InstantAlfred - triggered upon replying to OCB mail
2. Below are the flows that can be triggered from the OCB mail:
   1. Clicking **Renew now** button
      1. Should direct customers to the redirection page specific to renewals. [https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-12485&t=8z3CZ08bswnWytRa-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-12485&t=8z3CZ08bswnWytRa-4)
      2. Upon clicking "Proceed to pay" on the redirection screen, customers would be redirected to insurer's payment gateway. Mirroring flow of[](https://app.clickup.com/2197982/docs/232ey-50818/232ey-160438?block=block-697e930a-9058-47de-ade7-3927496c525d)
   2. Clicking on **alternative plans** hyperlink
      1. Should redirect customer to IM website where the renewal plan card would be the first plan card with a renewal tag against it. [https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-9785&t=Dcp5SmZu5ioKLudG-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-9785&t=Dcp5SmZu5ioKLudG-4)
      2. Renewal plan card should not have the dropdown option for copays. It should only display the renewal copay option.
         1. Renewal plan card when selected (by clicking "Renew now" button), should redirect the customer to the **renewal-specific redirection screen** for that plan (mirroring [](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-b6aaf4a2-4125-40a2-b0d9-8d6a1036e710)).
      3. On the IM quote page, the **plan category (BEST, GOOD, ENTRY LEVEL)** should **automatically default to the same category as the renewal plan**.
         **Example:**
         If the customer’s renewal plan is categorised under the '**GOOD**' plan type, then when the quote page loads, the '**GOOD**' tab should automatically be selected. The renewal plan card will always be shown as the first card at the top, below which all other available plans that fall under the '**GOOD**' category should be displayed. This ensures that the customer sees their current (renewal) plan along with similar alternatives grouped under the same plan category.
      4. Remaining cards follow the existing new business display & flow, except that all plan cards from the **renewal insurer** are hidden on the website (but remain available in IMCRM).
         **Example:**
         If the renewal plan is **Takaful Emarat – Mednet Silver Plus**, it appears as the first card. All other **Takaful Emarat plans**, including those with **NAS**, **Aafiya**, or other TPAs, are hidden from the website but remain accessible in IMCRM.
         Advisors will be able to hide/unhide plans from IMCRM (using existing feature in IMCRM). ![](https://t2197982.p.clickup-attachments.com/t2197982/d333ff15-bcab-4d97-a198-d7e7de35b9de/image.png)
      5. When customer clicks on **alternative plans** \- whether accessed through the OCB or the website - whichever occurs first - a notification email should be automatically sent to the advisor.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-247558?block=block-ba23a165-4d38-46f1-85eb-ed09ee7a40ec)
   3. **InstantAlfred** 1. Any customer reply to the OCB email should automatically trigger InstantAlfred for health renewals. 2. InstantAlfred should be triggered on **all** responses, without requiring specific keywords. 3. The assigned advisor must be CC’d on both:
      _ Customer replies to InstantAlfred
      _ Responses sent from InstantAlfred to the customer 4. Under the InstantAlfred Chat Logs section, it should be clearly indicated whether each message is from the customer, InstantAlfred, or an advisor.
      ![](https://t2197982.p.clickup-attachments.com/t2197982/ce2443a3-c98c-4919-a955-3f7cd52d7355/image.png)

## **C. User story: Customer flow if payment link** **and** **renewal premium are NOT available**

_(_**_Must have_** _feature)_
As a customer, I want to receive an OCB email containing basic plan details even when the pricing and payment link are missing, so that I can express my interest and receive assistance from an advisor.

## **Requirements:**

Customer flow if payment link **and** renewal premium are **NOT** available:
![](https://t2197982.p.clickup-attachments.com/t2197982/3e15ad6b-0429-4454-bf94-a76e1e628d3b/image.png)
[https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-16980&t=HHRNiX1HD78DdxyT-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-16980&t=HHRNiX1HD78DdxyT-4)

1. OCB in this case should include: 1. Advisor & member details 2. Insurer, network, plan name, annual limit, key hospitals & clinics, regions covered 3. Renewal copay 4. "Get Quote" button (redirecting to thank you page of this case) 5. Alternate options button (redirecting customers to IM quote page) 6. Renewal terms 7. InstantAlfred - triggered upon replying to OCB mail[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-75671ce4-353c-435a-b4bb-83d6d5dcd539)
   [https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-16981&t=OlsBc2rDHGGqEdYy-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-16981&t=OlsBc2rDHGGqEdYy-4)
1. To renew the plan _(when payment link_ _and_ _renewal premium are NOT available),_ once customer clicks on "Get quote" button (from OCA mail or website), they would be redirected to a thank you page with advisor details. [https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-19984&t=wuPbGInlyVgP1cFf-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-19984&t=wuPbGInlyVgP1cFf-4)
   1. Customers clicking on "Get quote" (from email or website - whichever action occurs first), should trigger a mail to the allocated advisor.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-247558?block=block-885a3547-7d62-48f0-8598-f7d6700e847d)
1. Clicking "See alternative quotes" button (from OCA mail) should follow[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-5493d520-1a5e-4377-8263-4b9ebb1f8374)
1. Once the renewal premium & payment link are received from the insurer, **OEs** have to trigger FTCs from IMCRM. Private ([https://app.clickup.com/2197982/docs/232ey-50818/232ey-160438](https://app.clickup.com/2197982/docs/232ey-50818/232ey-160438)) _(Please note that payment link and/or premium are usually added by OEs; advisors add just premiums and hence cannot trigger FTC mail)._

## **D. User story: Customer flow if** **only** **renewal premium is available**

_(_**_Must have_** _feature)_
As a customer, I want to receive an OCB email with the renewal premium when the payment link is missing, so that I can express my interest and receive assistance from an advisor.

## **Requirements:**

Customer flow when **only** renewal premium is available:

![](https://t2197982.p.clickup-attachments.com/t2197982/a0a69206-55ce-4fa0-9cc3-a84ca8c87ba3/image.png)

_Customer flow for use cases C and D remains the same; the only difference lies in the content shown in the OCB email and the thank you page._
[https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-13242&t=HHRNiX1HD78DdxyT-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-13242&t=HHRNiX1HD78DdxyT-4)

1. OCB in this case should include: 1. Advisor & member details 2. Insurer, network, plan name, annual limit, key hospitals & clinics, regions covered 3. Renewal copay 4. "Renew Now" button (redirecting to thank you page of this case) 5. Alternate options hyperlink (redirecting customers to IM quote page) 6. Renewal terms 7. InstantAlfred - triggered upon replying[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-75671ce4-353c-435a-b4bb-83d6d5dcd539)
   [https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-13243&t=HHRNiX1HD78DdxyT-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-13243&t=HHRNiX1HD78DdxyT-4)
1. Clicking **Renew now** (from OCB or website) button redirects customers to thank you page (of this use case).
1. When customer clicks on **Renew now** (either on OCB or website - whichever occurs first), should trigger an email to the advisor requesting to retrieve payment link for the plan [](https://app.clickup.com/2197982/docs/232ey-46718/232ey-247558?block=block-d15af40d-2dda-4084-8d11-77fe1e2190f6).

[https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-16259&t=HHRNiX1HD78DdxyT-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-16259&t=HHRNiX1HD78DdxyT-4)

3. Clicking alternative plans should follow[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-5493d520-1a5e-4377-8263-4b9ebb1f8374)

## E. User story: Manual triggering of OCB mails

_(_**_Must have_** _feature)_
As Renewal manager, I want to manually trigger OCB mails to customer so that I can ensure that renewal reminders are sent at the right time.

## **Requirements:**

1. If the OCB mail fails to send automatically, IMCRM should support manual resending of the failed email. Implemented through[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-197538?block=block-0f887870-604f-4c64-b2de-9a53901894f5)
   ![](https://t2197982.p.clickup-attachments.com/t2197982/0f1638c8-2078-420e-bdec-a3c69f872126/image%20-%202025-06-03T132707.344.png)
1. When manually resending a failed OCB, system should automatically select the appropriate template from the 3 available OCB templates based on the currently available data.

If any information has been updated at the time of manual sending—for example, if a payment link was initially unavailable but later added by an OE - the system should recognise the update and trigger the corresponding template accordingly.

2. This feature would only be available for users with the permission **renewals_batches_nonmotor**.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-197538?block=block-622998d8-f686-4fe6-aedb-65c846597258)

## **F. User story: Alternate options**

_(_**_Must have_** _feature)_
As IM, I want to include alternate options for customers in the OCB so that they can explore and select a different plan if needed.

## **Requirements:**

1. When the customer clicks on the alternative plans hyperlink/button (from the OCB/OCA emails), it should redirect them to IM's quote page against that lead.
   1. Renewal plan shared with the customer should be the first plan displayed on the quote page.
      [https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-9785&t=HHRNiX1HD78DdxyT-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=475-9785&t=HHRNiX1HD78DdxyT-4)
   2. On IM quotes page, plan category defaulting should work as specified under[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-34c1b384-5e0a-488c-a2e1-1b28d8fa2b2c)
   3. Remaining alternate plans should follow [](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-d0ec40eb-0f41-4f6b-86e9-a1c380cea30f)[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-197538?block=block-c2ba19bc-026f-48db-b5aa-1bfc41c3a35d).

## **G. User story: Renewal Comparison PDF**

_(_**_Should have_** _feature)_
As IM, I want to enhance the existing comparison PDF to support renewals, so that customers can view their renewal plan alongside other options.

## **Requirements:**

1. This would be specific to health renewals (no changes to new business comparison PDF considered in this FR scope; NB changes will be taken up separately).
2. First card must always be the renewal plan, tagged with a renewal banner.
3. If renewal premium is available, show "Renew Now"; otherwise show "Get Quote".
4. Remaining plan cards (up to 4) follow existing new business logic, with premiums calculated based on the customer’s age at renewal.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-197538?block=block-c2ba19bc-026f-48db-b5aa-1bfc41c3a35d)
5. Action buttons in the PDF should be:
   - “Renew Now” if premium is available.
   - "Get Quote" if premium is not available; customer should be redirected to thank you page of this use case[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-b09dbbde-f84a-432f-9c6a-400e90abd3f0)
   - “Apply Now” - existing new business flow
6. Follow the existing new business fallback flows, if any arise.
   [https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=496-45556&t=zVsKMRw2727a9LOX-4](https://www.figma.com/design/iW3lxTPaeoxLAY4gxkoo45/IM.ae--Health?node-id=496-45556&t=zVsKMRw2727a9LOX-4)

## **Additional Resources**

1. Existing renewal reminder from Gmass:

[GMASS Renewal reminder.pdf](https://t2197982.p.clickup-attachments.com/t2197982/b0150971-81c8-4213-b4f3-f6c8e7ed8a85/GMASS%20Renewal%20reminder.pdf)

1. Existing renewal reminder with renewal premiums and payment link:

[Renewal reminder with premiums and payment link.pdf](https://t2197982.p.clickup-attachments.com/t2197982/2a15df71-f5c9-490b-9fd8-c9f5dfcba0dd/Renewal%20reminder%20with%20premiums%20and%20payment%20link.pdf)

# FR: Enhanced scope for health renewals - CQF Flows & Tracking in IMCRM - 1

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 11/06/2025 | 1.0         | Alina           |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86ettyumv](https://app.clickup.com/t/86ettyumv)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Alina Poly                                                                           |     |
| **QA**                    |                                                                                      |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            |                                                                                      |     |
| **To Inform**             |                                                                                      |     |

## **A. Add new counters for health renewals - IMCRM**

**_(Should have)_**
As health renewals advisor, I want visibility on customer actions so that I can prioritize follow-ups accordingly.

## ✅ **Requirements**

Introduce the following counters in IMCRM for renewal advisors (similar to the “Pending Callbacks” counter):
![](https://t2197982.p.clickup-attachments.com/t2197982/1b2fffda-cc02-4137-9c31-1f38a87f3129/image.png)

1. **Requested Alternative**
   1. Triggered when the customer clicks on the **“Alternative Options”** button/hyperlink from the OCB email.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-5493d520-1a5e-4377-8263-4b9ebb1f8374)
2. **Renewal quote follow-up**
   1. Triggered when the customer clicks **“Get Quote”** from the OCB email or renewal plan card when **both premium & payment link are unavailable**.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-f4846d49-a77d-46cc-ba12-5414a3e221de)
3. **Payment link requested**
   1. Triggered when the customer clicks **“Renew Now”** in cases where **only renewal premium is available** and no payment link exists.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-adc99093-c8be-4f1f-983e-3bd1b3c9c80a)
4. Each counter should:
   1. Be displayed as a dropdown.
   2. List all applicable **Ref-IDs** under the dropdown.
   3. Ref-IDs should be hyperlinked, redirecting them to the **lead details page** in IMCRM when clicked.

## **B. Adjust renewal logic post-grace period (Premium displayed)**

**_(Must have)_**
As IM, I want to treat policies renewed after the grace period as new business so that pricing reflect new business rates.

## ✅ **Requirements**

1.  If a health policy is renewed **after the 30-day grace period** from the previous policy's expiry date, update the premium display logic in IMCRM as follows:
    1.  **Grace Period Calculation**
        - Grace period ends on **Previous Policy Expiry Date + 30 days + 1 day** (i.e. logic change is applied starting from the **31st day** after expiry)
    2.  **Lead Statuses**
        - Premium display logic change should apply only to renewal health leads that are currently **NOT** in one of the following statuses **after the grace period**:
          -       *   Transaction approved
            - Policy documents pending
            - Policy issued
            - Policy booked
            - Policy in queue
            - Policy failed
    3.  **Premium Source Change**
        - For leads meeting the above conditions, the **premium displayed in IMCRM** should be fetched from **IMCMS** based on **new business pricing**, not the renewal premium uploaded via Upload & Update.
2.  **Scope**
    - This logic applies **only to health renewal leads** where the **lead source is** **`renewal_upload`**.
    - Customer can be accessing the quote page either through website or OCB that was triggered 30 days before previous policy expiry.

## **C. Website display logic for renewals post-grace period**

**_(Must have)_**
As a customer visiting the IM website, I want clear visibility of current pricing and actions when I’ve missed my renewal grace period, so I know how to proceed.

## ✅ **Requirements**

- If the customer accesses the **renewal plan card** via website or OCB mail **after the grace period**:
  - Do **not display the renewal premium**.
  - Instead, fetch and display the **new business premium from IMCMS**.
  - CTA button should be changed to **“\*\***Renewal Expired – Reapply\***\*”** **case 1 without premium and TYP of use case 3\*\***;\*\* **case 2- renewal tag is removed, cms rate and displayed under normal NB, follow NB process with same insurer plans displayed - DISCUSS DURING DT** For how long should this logic work? till that lead status means into "transaction approved"?
  - Clicking on **“Renewal Expired – Reapply”** for a specific button should redirect customer to summary page of that plan (following new business process)

on PDF, if clicking on renew now after grace period - what is technically feasible?

## **D. Display and filter health leads across Departments**

**_(Should have)_**
As business, I want to have a comprehensive view of all health leads across various departments, so that I can ensure no potential business is overlooked regardless of which team handles the lead.

## ✅ Requirements

1\. Inclusion of **Department** filter:
\- Implement a department filter under the health lead list view to filter leads across different departments, including retail medical, organic, motor renewal, etc.
\- Ensure the filter allows visibility of all health leads, irrespective of the team assigned, to facilitate oversight over leads that may be handled by non-health specific teams.

## **E. Replace subteam filter with team filter**

**_(Must have)_**
As business, I want to accurately capture and display team information, so that I can pull accurate reports for informed decision-making and analysis.

## ✅ Requirements

1\. Display of Team filter
\- Replace subteam filter with **team** on the health lead list view and lead details page to align with organizational reporting structures.
\- Ensure capturing and displaying team information accurately.
Health lead list view: ![](https://t2197982.p.clickup-attachments.com/t2197982/909a9689-331c-439a-b0b5-8b192b97c103/image.png)
Lead detail page: In this page, the team should be **autofilled** depending upon the advisor assigned to the lead.
![](https://t2197982.p.clickup-attachments.com/t2197982/c2428ca1-df26-4aba-b91d-095d8db75087/image.png)
2\. Team option should be available in the export document as well
_Note: These updates should not affect existing functionalities and should integrate seamlessly with current processes to improve the oversight and management of health insurance leads._

# FR: Enhanced scope for health renewals - CQF Flows & Tracking in IMCRM - 2

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 11/06/2025 | 1.0         | Alina           |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86ettyumv](https://app.clickup.com/t/86ettyumv)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Alina Poly                                                                           |     |
| **QA**                    |                                                                                      |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            |                                                                                      |     |
| **To Inform**             |                                                                                      |     |

## **A. Add new counters for health renewals - IMCRM**

**_(Should have)_**
As health renewals advisor, I want visibility on customer actions so that I can prioritize follow-ups accordingly.

## ✅ **Requirements**

Introduce the following counters in IMCRM for renewal advisors (similar to the “Pending Callbacks” counter):
![](https://t2197982.p.clickup-attachments.com/t2197982/1b2fffda-cc02-4137-9c31-1f38a87f3129/image.png)

1. **Requested Alternative**
   1. Triggered when the customer clicks on the **“Alternative Options”** button/hyperlink from the OCB email.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-5493d520-1a5e-4377-8263-4b9ebb1f8374)
2. **Renewal quote follow-up**
   1. Triggered when the customer clicks **“Get Quote”** from the OCB email or renewal plan card when **both premium & payment link are unavailable**.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-f4846d49-a77d-46cc-ba12-5414a3e221de)
3. **Payment link requested**
   1. Triggered when the customer clicks **“Renew Now”** in cases where **only renewal premium is available** and no payment link exists.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-adc99093-c8be-4f1f-983e-3bd1b3c9c80a)
4. Each counter should:
   1. Be displayed as a dropdown.
   2. List all applicable **Ref-IDs** under the dropdown.
   3. Ref-IDs should be hyperlinked, redirecting them to the **lead details page** in IMCRM when clicked.

## **B. Adjust renewal logic post-grace period (Premium displayed)**

**_(Must have)_**
As IM, I want to treat policies renewed after the grace period as new business so that pricing reflect new business rates.

## ✅ **Requirements**

1.  If a health policy is renewed **after the 30-day grace period** from the previous policy's expiry date, update the premium display logic in IMCRM as follows:
    1.  **Grace Period Calculation**
        - Grace period ends on **Previous Policy Expiry Date + 30 days + 1 day** (i.e. logic change is applied starting from the **31st day** after expiry)
    2.  **Lead Statuses**
        - Premium display logic change should apply only to renewal health leads that are currently **NOT** in one of the following statuses **after the grace period**:
          -       *   Transaction approved
            - Policy documents pending
            - Policy issued
            - Policy booked
            - Policy in queue
            - Policy failed
    3.  **Premium Source Change**
        - For leads meeting the above conditions, the **premium displayed in IMCRM** should be fetched from **IMCMS** based on **new business pricing**, not the renewal premium uploaded via Upload & Update.
2.  **Scope**
    - This logic applies **only to health renewal leads** where the **lead source is** **`renewal_upload`**.
    - Customer can be accessing the quote page either through website or OCB that was triggered 30 days before previous policy expiry.

## **C. Website display logic for renewals post-grace period**

**_(Must have)_**
As a customer visiting the IM website, I want clear visibility of current pricing and actions when I’ve missed my renewal grace period, so I know how to proceed.

## ✅ **Requirements**

- If the customer accesses the **renewal plan card** via website or OCB mail **after the grace period**:
  - Do **not display the renewal premium**.
  - Instead, fetch and display the **new business premium from IMCMS**.
  - CTA button should be changed to **“\*\***Renewal Expired – Reapply\***\*”** **case 1 without premium and TYP of use case 3\*\***;\*\* **case 2- renewal tag is removed, cms rate and displayed under normal NB, follow NB process with same insurer plans displayed - DISCUSS DURING DT** For how long should this logic work? till that lead status means into "transaction approved"?
  - Clicking on **“Renewal Expired – Reapply”** for a specific button should redirect customer to summary page of that plan (following new business process)

on PDF, if clicking on renew now after grace period - what is technically feasible?

## **D. Display and filter health leads across Departments**

**_(Should have)_**
As business, I want to have a comprehensive view of all health leads across various departments, so that I can ensure no potential business is overlooked regardless of which team handles the lead.

## ✅ Requirements

1\. Inclusion of **Department** filter:
\- Implement a department filter under the health lead list view to filter leads across different departments, including retail medical, organic, motor renewal, etc.
\- Ensure the filter allows visibility of all health leads, irrespective of the team assigned, to facilitate oversight over leads that may be handled by non-health specific teams.

## **E. Replace subteam filter with team filter**

**_(Must have)_**
As business, I want to accurately capture and display team information, so that I can pull accurate reports for informed decision-making and analysis.

## ✅ Requirements

1\. Display of Team filter
\- Replace subteam filter with **team** on the health lead list view and lead details page to align with organizational reporting structures.
\- Ensure capturing and displaying team information accurately.
Health lead list view: ![](https://t2197982.p.clickup-attachments.com/t2197982/909a9689-331c-439a-b0b5-8b192b97c103/image.png)
Lead detail page: In this page, the team should be **autofilled** depending upon the advisor assigned to the lead.
![](https://t2197982.p.clickup-attachments.com/t2197982/c2428ca1-df26-4aba-b91d-095d8db75087/image.png)
2\. Team option should be available in the export document as well
_Note: These updates should not affect existing functionalities and should integrate seamlessly with current processes to improve the oversight and management of health insurance leads._

# F. Automated Renewal Lead Creation

1.  Check the leads with the payment status "Paid" and "Partially Paid"
    1. Ignore if the lead status is set to be "Policy cancelled" or "Policy cancelled and reissued" or "Cancellation pending"
    2. Check the "Expiry date" field in the policy details section.
2.  Nothing to be checked in "Send Update" status
3.  Nothing to be checked in "Embedded Product" also
4.  Create a renewal lead 60 days prior to the health policy expiry date
    1. Same as the current behavior:-
       1. Lead source should be renewal_upload
       2. Lead status to be set to "New Lead"
       3. These leads should not be included in the ILA and Buy leads
       4. No OCB email should be sent at this point
5.  Link the renewal_upload lead to the previous lead booked 1. Once a renewal lead is created, the section displaying last year's policy details should be activated.
    ![](https://t2197982.p.clickup-attachments.com/t2197982/8a4a71b2-9ad8-47c9-ac38-d963bf820653/image.png)
6.        1. Add the previous Ref ID as a hyperlink and ensure that clicking the Ref ID opens the old lead in a new tab to ensure that the user will be able to access the previous lead to see details and documents from the previous year
7.  Under "Uploaded Leads" in the renewals module, the Renewal Manager should continue to see how many leads are created and it's status 1. In case of bad data, the Renewal Manager should be able to export and reupload the file using the existing upload and create functionality as per the existing behavior
    ![](https://t2197982.p.clickup-attachments.com/t2197982/8f086037-c423-4b29-b375-e9dc8eab49e3/image.png)
8.        1. Under the "Search" in the renewal module, the Renewal Manager should be able to search for all renewal\_upload leads created within a selected policy expiry date range and export the search results if needed to ensure that the leads created are complete.

# F.1.0 Automation - Upload & Update data

- When a renewal lead is created **60 days prior to the policy expiry date**, the data should be available for export with **pre-filled details** in an Excel sheet.
- An **"Export Renewal Lead"** button should be incorporated within the **Upload and Update** section.
- The following data points will be **pre-filled** in the exported Excel sheet:

| Upload and Create           | Mandatory/Optional | IMCRM Section    | Policy booked Lead          | IMCRM Section    | Renewal_Upload_Mapping      |
| --------------------------- | ------------------ | ---------------- | --------------------------- | ---------------- | --------------------------- |
| Customer Name               | Mandatory          | Customer Profile | Insured First and Last Name | Customer Profile | Insured First and Last Name |
| Customer Email ID           |                    | Customer Profile | Email                       | Customer Profile | Email                       |
| Customer Mobile Number      |                    | Customer Profile | Mobile Number               | Customer Profile | Mobile Number               |
| Plan code                   |                    |                  |                             |                  |                             |
| Previous Policy Number      |                    | Policy Details   | Policy Number               | Policy Details   | Previous Policy Number      |
| Previous Policy Expiry Date |                    | Expiry date      | Previous Policy Expiry Date |
| Previous Policy Premium     |                    | Total Price      | Previous Policy Premium     |
| Advisor Email               |                    |                  |                             |                  |                             |
| Renewal Premium             |                    |                  |                             |                  |                             |
| Renewal Co-Pay              |                    |                  |                             |                  |                             |
| Payment Link                |                    |                  |                             |                  |                             |
| Member Name 1               |                    | Member details   | Member Name                 | Member details   | Member Name                 |
| DOB 1                       |                    | DOB              | DOB                         |
| Nationality 1               |                    | Nationality      | Nationality                 |
| Gender 1                    |                    | Gender           | Gender                      |
| Relation 1                  |                    | Relation         | Relation                    |
| Emirate of Visa 1           |                    | Emirates of Visa | Emirate of Visa             |
| Member Category 1           |                    | Member Category  | Member Category             |
| Member Name 2               |                    | Member details   | Member Name                 |                  | Member Name                 |
| DOB 2                       |                    | DOB              |                             | DOB              |
| Nationality 2               |                    | Nationality      |                             | Nationality      |
| Gender 2                    |                    | Gender           |                             | Gender           |
| Relation 2                  |                    | Relation         |                             | Relation         |
| Emirate of Visa 2           |                    | Emirates of Visa |                             | Emirates of Visa |
| Member Category 2           |                    | Member Category  |                             | Member Category  |
| Member Name 3               |                    | Member details   | Member Name                 |                  | Member Name                 |
| DOB 3                       |                    | DOB              |                             | DOB              |
| Nationality 3               |                    | Nationality      |                             | Nationality      |
| Gender 3                    |                    | Gender           |                             | Gender           |
| Relation 3                  |                    | Relation         |                             | Relation         |
| Emirate of Visa 3           |                    | Emirates of Visa |                             | Emirates of Visa |
| Member Category 3           |                    | Member Category  |                             | Member Category  |
| Member Name 4               |                    | Member details   | Member Name                 |                  | Member Name                 |
| DOB 4                       |                    | DOB              |                             | DOB              |
| Nationality 4               |                    | Nationality      |                             | Nationality      |
| Gender 4                    |                    | Gender           |                             | Gender           |
| Relation 4                  |                    | Relation         |                             | Relation         |
| Emirate of Visa 4           |                    | Emirates of Visa |                             | Emirates of Visa |
| Member Category 4           |                    | Member Category  |                             | Member Category  |
| Lead Remark                 |                    |                  |                             |                  | Renewal or New Business     |
| Notes                       |                    |                  |                             |                  |                             |
| Renewal policy Ref ID       |                    |                  |                             |                  |                             |
|                             |                    |                  |                             |                  |                             |

- If a policy has already expired, the Lead Remark should be mapped as "New Business".
- A Date Range Filter should be incorporated within the Upload and Update section.
- Data should be available for download for policies that are set to expire within the next 60 days.
  1. The following **Renewal Lead Statuses** should be **excluded** from the exported Excel sheet:
     - Transaction Approved
     - Policy Booked
     - Policy Issued
     - Policy document pending
     - Policy in queue

Once the data downloaded, the CQF team will map the Renewal Premium and Renewal Link

Auto-Create a Renewal Lead and linked to Main Lead
Auto- Assigned the Renewal Lead
Update Renewal and Payment Link

# FR: \*Automated Email Notification for Advisors(QA testing)

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments**              |
| ---------- | ----------- | --------------- | ------------------------- |
| 02/04/2025 | 1.0         | Alina           | Based on feedback from MB |
| 15/05/2025 | 1.1         | Alina           | Use case B added          |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86et0xfhx](https://app.clickup.com/t/86et0xfhx)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Alina Poly                                                                           |     |
| **QA**                    |                                                                                      |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            |                                                                                      |     |
| **To Inform**             |                                                                                      |     |

## **A. User story- Notify advisors about customer opting for alternative options**

_(_**_Should have_** _- Improves advisor response time and customer follow-up efficiency)_
As IM, I want to notify advisors when their customer chooses to explore alternate options instead of renewing the expiring plan so that they are informed and can take necessary action.

## **Requirements:**

1. When a customer clicks the alternate options hyperlink/button in the OCB email, a notification email must be sent to the allocated advisor _(An advisor is always allocated during upload and creation.)_[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-2fa4970f-3248-4db5-ab65-a689a986b185)
2. Routing structure and content as follows:

| From                                                                                                                                                                                                                                                                                                                                                                                                                                                  | **Alfred** <[alfred@alert.insurancemarket.email](mailto:alfred@alert.insurancemarket.email)\> |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | --- |
| To                                                                                                                                                                                                                                                                                                                                                                                                                                                    | <Advisor's email>                                                                             |
| Reply to                                                                                                                                                                                                                                                                                                                                                                                                                                              | <[health@insurancemarket.ae](mailto:health@insurancemarket.ae)\>                              |
| CC                                                                                                                                                                                                                                                                                                                                                                                                                                                    | <[health@insurancemarket.ae](mailto:health@insurancemarket.ae)\>                              |     |
| Email Subject                                                                                                                                                                                                                                                                                                                                                                                                                                         | **Urgent: Customer Exploring Alternatives – \[Reference ID\]**                                |
| **Preview Text:** Act swiftly to ensure customer retention!                                                                                                                                                                                                                                                                                                                                                                                           |
| Email Content:                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| Dear \[Advisor's Name\],<br><br>We would like to inform you that your customer, **\[Customer Name\]** : **\[Reference ID\]**, is considering alternative insurance options rather than renewing their current plan.<br><br>Please reach out to the customer at your earliest convenience to assist them with their needs and ensure they find the most suitable coverage.<br><br><br>Best regards,<br>[InsuranceMarket.ae](http://InsuranceMarket.ae) |

1. In the unlikely event that no advisor is allocated at the time the customer clicks the option in the OCB email/website, the notification email should be routed to [**health@insurancemarket.ae**](mailto:health@insurancemarket.ae)**.** _(Advisors are typically allocated during lead upload and create.)_
2. The **Ref-ID** in the email body should be a clickable hyperlink that redirects the advisor to the lead detail page when clicked.
3. Within the specific lead's detail page, in the Email Status section, add a new entry that includes the subject line of the triggered email.

![](https://t2197982.p.clickup-attachments.com/t2197982/d8ca7acc-bca7-463c-8cd0-f7748de87c54/image.png)

4. Every instance where the customer clicks on alternative options should be captured under the UTM reports. Private ([https://app.clickup.com/2197982/docs/232ey-26007/232ey-41687](https://app.clickup.com/2197982/docs/232ey-26007/232ey-41687))

**UTM Structure:**

`utm_source=email&utm_medium=ocb&utm_campaign=health_renewal_[use_case]`

Where `[use_case]` = `altopt`.

## **B. User story - Advisor notification: Renewal** **without** **renewal premium & payment link**

_(_**_Should have_** _- Improves advisor response time and customer follow-up efficiency)_
As IM, I want to notify advisors when their customer chooses to renew an expiring plan, without a renewal premium or payment link available, so that the advisor can take the necessary action to retrieve the missing details.

## **Requirements:**

1. When a customer clicks the **“Get Quote”** button from the OCB email or the website, a notification email should be sent to the allocated advisor.[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-f4846d49-a77d-46cc-ba12-5414a3e221de)
2. Routing structure and content as follows:

_Preview text does not exceed 60 characters for optimal display in Gmail and mobile inboxes._

| From                                                                                                                                                                                                                                                                                                                                                                                                                                                                          | **Alfred** <[alfred@alert.insurancemarket.email](mailto:alfred@alert.insurancemarket.email)\> |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | --- |
| To                                                                                                                                                                                                                                                                                                                                                                                                                                                                            | <Advisor's email>                                                                             |
| Reply to                                                                                                                                                                                                                                                                                                                                                                                                                                                                      | <[health@insurancemarket.ae](mailto:health@insurancemarket.ae)\>                              |
| CC                                                                                                                                                                                                                                                                                                                                                                                                                                                                            | <[health@insurancemarket.ae](mailto:health@insurancemarket.ae)\>                              |     |
| Email Subject                                                                                                                                                                                                                                                                                                                                                                                                                                                                 | **Urgent:** **Renewal Request – Premium & Payment Link Missing – \[Reference ID\]**           |
| **Preview Text:** Urgent: Renewal Request – Advisor Action Needed!                                                                                                                                                                                                                                                                                                                                                                                                            |
| Email Content:                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| Dear \[Advisor's Name\],<br><br>We would like to inform you that your customer, **\[Customer Name\] :** **\[Reference ID\]**, has submitted a request to renew their health insurance plan. However, the **renewal premium** and **payment link** are currently **unavailable**.<br>Please contact the insurer to obtain the necessary details and assist the customer with the renewal process.<br><br><br>Best regards,<br>[InsuranceMarket.ae](http://insurancemarket.ae/) |

1. The **Ref-ID** in the email body should be a clickable hyperlink that redirects the advisor to the lead detail page when clicked.
2. Within the specific lead's detail page, in the Email Status section, add a new entry that includes the subject line of the triggered email.
3. Every instance where the customer clicks on "Get quote" button should be captured under the UTM reports. Private ([https://app.clickup.com/2197982/docs/232ey-26007/232ey-41687](https://app.clickup.com/2197982/docs/232ey-26007/232ey-41687))

**UTM Structure:**

`utm_source=email&utm_medium=ocb&utm_campaign=health_renewal_[use_case]`

Where `[use_case]` = `nopremandlink`.

## **C. User story - Advisor notification: Renewal with** **only** **renewal premium**

_(_**_Should have_** _- Improves advisor response time and customer follow-up efficiency)_
As IM, I want to notify advisors when their customer chooses to renew an expiring plan, with renewal premium but no payment link available, so that the advisor can retrieve the payment link.

## **Requirements:**

1. When a customer clicks the **“Renew Now”** button from the OCB email or the website (in cases where only the premium is available), a notification email must be sent to the allocated advisor[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-160278?block=block-adc99093-c8be-4f1f-983e-3bd1b3c9c80a)
2. Routing structure and content as follows:

| From                                                                                                                                                                                                                                                                                                                                                                                                                                                                | **Alfred** <[alfred@alert.insurancemarket.email](mailto:alfred@alert.insurancemarket.email)\> |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | --- |
| To                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | <Advisor's email>                                                                             |
| Reply to                                                                                                                                                                                                                                                                                                                                                                                                                                                            | <[health@insurancemarket.ae](mailto:health@insurancemarket.ae)\>                              |
| CC                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | <[health@insurancemarket.ae](mailto:health@insurancemarket.ae)\>                              |     |
| Email Subject                                                                                                                                                                                                                                                                                                                                                                                                                                                       | **Urgent:** **Renewal Request – Payment link Missing – \[Reference ID\]**                     |
| **Preview Text:** Urgent: Renewal Request – Advisor Action Needed!                                                                                                                                                                                                                                                                                                                                                                                                  |
| Email Content:                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| Dear \[Advisor's Name\],<br><br>This is to inform you that **\[Customer Name\] :** **\[Reference ID\]**, has submitted a renewal request for their health insurance plan. Although the renewal premium is accessible, the **payment link is currently missing**.<br><br>Please coordinate with the insurer to secure the payment link and assist customer in completing their renewal.<br><br><br>Best regards,<br>[InsuranceMarket.ae](http://insurancemarket.ae/) |

1. The **Ref-ID** in the email body should be a clickable hyperlink that redirects the advisor to the lead detail page when clicked.
2. Within the specific lead's detail page, in the Email Status section, add a new entry that includes the subject line of the triggered email.
3. Every instance where the customer clicks on "Renew Now" button should be captured under the UTM reports. Private ([https://app.clickup.com/2197982/docs/232ey-26007/232ey-41687](https://app.clickup.com/2197982/docs/232ey-26007/232ey-41687))

**UTM Structure:**

`utm_source=email&utm_medium=ocb&utm_campaign=health_renewal_[use_case]`

Where `[use_case]` = `nopaymentlink`.

### Visual Flow Diagram

The following flow illustrates the end-to-end advisor notification logic:

1. Customer clicks on a CTA in the OCB email or website.
2. System receives the event and determines the applicable scenario based on the customer's action and available data.
3. Based on the scenario:
   - **Use Case A:** Customer explores alternative options.
   - **Use Case B:** Customer chooses to renew but neither premium nor payment link is available.
   - **Use Case C:** Customer chooses to renew with premium available, but payment link is missing.
4. Advisor is notified via email with a Ref-ID.
5. Advisor clicks on the Ref-ID, opening the lead detail page.
6. System logs the action in the Email Status section.

![](https://t2197982.p.clickup-attachments.com/t2197982/e8d6ed6a-4122-407d-ab61-5e253f74b843/final_email_status_only_flowchart.png)

# Health CQF SOP

1. Upload and update should ideally be done 45 days prior to policy expiry date. This implies that upload & create has to be done before that (60 days possible?)
2. CQF team allocation to be done by CQF manager
3. CQF manager should then share the exported sheet with the CQF team.
4. CQF team should collect all necessary data from the distribution inbox and expiring policy documents for the renewal leads they are assigned.
5. Once the team members collect and enter the required details, the CQF manager should upload the document to IMCRM. This ideally has to be done 45 days before the policies in a specific batch expire.

If mails bounce, cqf team would be responsible for making sure that the customer receives the mail - manually track mail ids (with help from RM)

# X Advisor Journey-OCB renewal option

**New flow required by business and cannot mirror existing motor flow**
Separate OCB template is reqd for send OCA button of renewals
It should imply that its new quote request
First OCB should reply to the same email trail
If plans are not selected, 6 plans should automatically be selected - Logic to be implemented
Comparison PDF - first plan should be the renewal plan + remaining plans

Restrict advisors from sending OCB without selecting 5 plans

# FTC mail for renewals (phase 2)

# Renewal allocation + Redirection (phase 2)

# Health CQF scope as of 25th March, 25

| Feature                                                                                                    | FR                                                                                   | Status                                                                             | Dev estimate | Release Date               |
| ---------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------- | ------------ | -------------------------- |
| Display premiums for all health plans from incumbent insurers                                              | Private ([https://app.clickup.com/t/86eqfu4em](https://app.clickup.com/t/86eqfu4em)) | Done                                                                               |              |                            |
| Upload & update                                                                                            | Private ([https://app.clickup.com/t/86eqqxbf2](https://app.clickup.com/t/86eqqxbf2)) | In testing                                                                         |              | May 22 (with payment link) |
| Fetching of plans                                                                                          | Private ([https://app.clickup.com/t/86eqvx7rg](https://app.clickup.com/t/86eqvx7rg)) | DTFAR approved                                                                     |              |                            |
| Customer journey - renewal OCB, doc upload (if premium not available), payment link (if premium available) | Private ([https://app.clickup.com/t/86eqvx56q](https://app.clickup.com/t/86eqvx56q)) | UI/UX feedback collection + waiting on business to provide important notes for OCB |              |                            |
| Automated Email Notification for Advisors: Alternative Quotes                                              | Private ([https://app.clickup.com/t/86et0xfhx](https://app.clickup.com/t/86et0xfhx)) | In review                                                                          |              |                            |
|                                                                                                            |                                                                                      |                                                                                    |              |                            |

**Extended scope (as phase 2)**

- Automated renewal allocation
- Tier-R redirection
- FTC mail specific to renewals

# Health

| **IMCRM Sections**          | **Fields**                                                           | **Notes**                                                                      | **Required** |
| --------------------------- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------ | ------------ |
| Health detail               | Customer type                                                        | Based on AML screening (check the issue - unable to select the plan if entity) | No           |
| Advisor                     | Based on the upload and update file                                  | No                                                                             |
| Quote details               | For whom do you require health insurance?                            | Based on the number of members:<br><br><br><br>                                | Yes          |
| Currently insured with      | Based on the Provider Name from the policy booked lead               | Yes                                                                            |
| Type of plan                | Based on the type of plan of the policy booked lead                  | Yes                                                                            |
| Customer profile            | First name                                                           | First name from the policy booked lead                                         | Yes          |
| Last name                   | Last name from the policy booked lead                                | Yes                                                                            |
| Insured first name          | Insured first name from the policy booked lead                       | Yes                                                                            |
| Insured last name           | Insured last name from the policy booked lead                        | Yes                                                                            |
| Mobile number               | Mobile number from the policy booked lead                            | Yes                                                                            |
| Email                       | Email from the policy booked lead                                    | Yes                                                                            |
| Nationality                 | Nationality from the policy booked lead                              | Yes                                                                            |
| Date of birth               | Date of birth from the policy booked lead                            | Yes                                                                            |
| Emirate of visa             | Emirate of visa from the policy booked lead                          | Yes                                                                            |
| Gender                      | Gender from the policy booked lead                                   | Yes                                                                            |
| Marital status              | Marital status from the policy booked lead                           | Yes                                                                            |
| Salary band                 | Salary band from the policy booked lead                              | Yes                                                                            |
| Member category             | Member category from the policy booked lead                          | Yes                                                                            |
| Member details              | Member details (in table format)                                     | Member details from the policy booked lead                                     | Yes          |
| Last year's policy details  | Renewal batch number                                                 | Based on the previous policy expiry date (automated)                           | Yes          |
| Previous policy number      | Policy number from the policy booked lead                            | Yes                                                                            |
| Previous policy expiry date | Expiry date from the policy booked lead                              | Yes                                                                            |
| Previous policy premium     | Total price from the policy booked lead (should include send update) | No                                                                             |
| Previous policy start date  | Start date from the policy booked lead                               | Yes                                                                            |
| Previous advisor            | Advisor from the policy booked lead                                  | No                                                                             |

# FR: CQF Manager featured

- [x] upload & create feature
- [x] add whatsapp OCB to the scope
- [x] OCB mail feature from IMCRM - discussed during DT : separate FR
- [ ] OCB mail feature from the Customer perspective
- [x]     upload & renewal
  - [x] excel template with required fields
- [x] permissions for Renewal manager
- [ ] miro flow of the feature, if needed
- [x] how to handle takaful emarat scenario where each family member has a separate policy - here it is the TE process that has to be managed
- [ ] mention premiums are indicative
- [ ] Do we have to specify the OCB timeframe (30-45 days prior) as once the manual upload is done, OCB mail can be triggered.
- [x]     In insly, currently for TE family policies, they will be under different policy numbers but with the same insurer name, email address and policy start date
  - [x] so the concern is that it has to be clubbed manually and this has to be automated - is there a solution for this when LST Insly is launched?
- [ ] OCB mail should be just one mail for families
- [x]     Father n mother took policy in jan but in feb, policy was taken for child. so those will be 2 diff policies but assigned to diff advisors cause no mapping is done so that they can be under the same advisor
  - [x] is this under scope?
- [ ] How to convey to the customer that the alternate options do not show the final rate/premium to be paid? Subject to change
- [ ] fetch Plans
- [x] Skip plan - in case if premium is not available
- [x] **CONVEY TO AGATHA** - reminder mail is not sent in Motor CQF. Instead, OCB mail is sent with renewal plan n alternate options
- [x] Confirm with Paula what the SEND OCB Button in IMCRM should do.
- [x] Logic for alternate options will, be shared by Agatha - network mapping
- [ ]     Details to collect from Agatha
  - [x] Logic for Alternate options - network mapping
  - [ ] Current wording for indicative prices of alternate logic
  - [ ] Current wording for Renewal Plans sent without Premium prices

## **Version Table:**

| **Date** | **Version** | **Modified by** | **Comments** |
| -------- | ----------- | --------------- | ------------ |
|          |             |                 |              |

| **List or ClickUp tasks** |                             |     |
| ------------------------- | --------------------------- | --- |
| **Sprint**                | \[Project manager to fill\] |     |
| **Business Requestors**   |                             |     |
| **Approvers**             |                             |     |
| **Budget Approval**       |                             |     |
| **CTO**                   |                             |     |
| **CDTO**                  |                             |     |
| **CMO**                   |                             |     |
| **CPO - UI/UX**           |                             |     |
| **Content**               |                             |     |
| **PM**                    |                             |     |
| **BA/DTM/Champion**       | Alina Poly                  |     |
| **QA**                    |                             |     |
| **CPO**                   |                             |     |
| **Developers**            |                             |     |
| **To Inform**             |                             |     |

_Upload and Create of Renewals is a feature that will be automated once LST: Insly is launched._

## **A. User story: Upload and Update Renewals**

As CQF Manager, I want to be able to update the leads created with additional information so that leads are modified with Renewal details.

## **Requirements:**

1. CQF Manager can download the following spreadsheet from IMCRM and pass it . For this, they can make use of the following template:

[https://docs.google.com/spreadsheets/d/1KpV8IHG04nRY0cJ8s8jQGv-pESbn39BbwP203HhhTqs/edit#gid=0](https://docs.google.com/spreadsheets/d/1KpV8IHG04nRY0cJ8s8jQGv-pESbn39BbwP203HhhTqs/edit#gid=0)

1. Below table shows the data fields that are to be included in the template, and this table is to be displayed in the IMCRM. **_This table would be specific to Health's LOB_**_._

| **FIELD NAME**             | **DESCRIPTION**                                                   | **REQUIRED**            | **MAX SIZE** |
| -------------------------- | ----------------------------------------------------------------- | ----------------------- | ------------ | --- |
| Customer Name              | Field accepts only alphabets                                      | Yes                     | 100          |
| Customer Email             | Field accepts alphanumeric and special characters                 | Optional                | 300          |
| Customer Number            | Field accepts only numbers                                        | Optional                | 20           |
| Gender                     | Field accepts alphabets and special characters                    | Yes                     | 20           |
| Marital Status             | Field accepts only alphabets                                      | Yes                     | 20           |
| Date of Birth              | Field accepts numeric values. Format should be DD/MM/YYYY         | Yes                     | 10           |
| Nationality                | Field accepts only alphabets                                      | Yes                     | 15           |
| Emirate of Visa            | Field accepts only alphabets                                      | Yes                     | 15           |
| Insurance Type             | Field accepts only alphabets                                      | HEA                     | Yes          | 4   |
| Current Insurance Provider | Field accepts alphabets                                           | Yes                     | 20           |
| Current Plan Type          | Field accepts alphabets and special characters                    | Entry-Level, Good, Best | Optional     | 15  |
| New Advisor Email          | Field accepts alphanumeric and special characters                 | Yes                     | 300          |
| Policy Number              | Field accepts alphanumeric and special characters                 | Yes                     | 30           |
| Policy Start Date          | Start Date of the insurance - Format should be DD/MM/YYYY         | Optional                | 10           |
| Policy End Date            | End Date of the insurance - Format should be DD/MM/YYYY           | Yes                     | 10           |
| Batch                      | Batch Number assigned to accept Alphanumeric & special characters | Yes                     | 10           |
| New Insurance Provider     | Field accepts alphabets.                                          | Yes                     | 20           |
| New Network                | Field accepts alphabets and special characters                    | Yes                     | 30           |
| New Co-Pay                 | Field accepts alphanumeric and special characters                 | Yes                     | 200          |
| Premium - Renewals         | Field accepts numeric values                                      | Yes                     | 15           |
| Claims History             | Field accepts alphanumeric & special characters                   | Optional                | 300          |
| NC Letter                  | Field accepts only alphabets                                      | Yes, No                 | No           | 5   |
| Sum Insured                | Field accepts numeric values                                      | Yes                     | 10           |
| Insurer Quote No.          | Field accepts alphanumeric & special characters                   | No                      | 10           |
| Previous Advisor Mail      | Field accepts alphanumeric and special characters                 | No                      | 300          |
| Notes                      | Field accepts alphanumeric and special characters                 | No                      | 50           |

1.  Managers will be provided with the Skip Plans functionality before uploading the document.
    -       *   Different Skip Options would be Yes and No. They can choose to Skip plans that do not have Premium (Price) available.
      - If the skip option selected is No, then batches are created, Advisors are assigned, and plans are fetched.
      - If the Skip option selected is Yes, then batches are created, Advisors are assigned, but no plans are fetched.
        1. In this scenario, when Renewals Manager triggers the Send Mail option, the email sent to the customer will not include any plans. (Follow existing OCB Template for zero plan) INCLUDE ZERO PLAN OCB TEMPLATE
2.  Once the required details are entered, renewal manager can upload the updated document.
    1. This updates the existing Leads with additional details
    2. Updated leads are then allocated to Advisors

## **C. User story: Fetch Plans and Send Emails**

As a renewals manager, I want to be able to view the uploaded batches so that I can fetch available plans and send them to those customers whose policies are about to expire.

## **Requirements:**

1.  Against each uploaded Renewal Batch, Renewals Manager will have the option to Fetch Plans and Send Emails
2.  When Fetch Plans against an entry is selected, plans for each lead are fetched with the help of APIs and rating tables.
3.  Plans fetched and sent to customers should have the Renewal Plan for the existing policy displayed.
    1.        1. 2 scenarios come into play here: Renewal policies with Premium available and Renewal policies with No Premium available
        2. The sole difference in the OCB templates for the scenarios mentioned is the distinction between the "Buy Now" and "Under Review" actions.
4.  After plans are fetched, Manager can send them to the customer. This will be sent to them as a reminder mail (OCB).
    1. When OCB mail is sent to the customer, the assigned advisor will also be CC'd on it.

![](https://t2197982.p.clickup-attachments.com/t2197982/af31508f-b44e-409b-bfc6-d5ce150f3537/image.png)

##

## **G. User story: Send OCB Mail**

##

As an Advisor, I want the functionality to send OCB emails to Customers so that I can send them plan details when required.

## **Requirements:**

This feature is primarily needed in scenarios where Renewal premiums are not available while sending the initial OCB mail (upon fetching plans).
Once the Premium is available, Advisor should be able to edit the plan details, add the premium and send the plan to Customer

**LINK SEND OCB HEALTH FR**

##

## **Open Questions:**

| **Date Raised** | **Question/Concern**                                                                                                                                                                                                                                                                                                                                                                                                                    | **Raised by** | **Status** | **Outcome**                                                                                                                                                                                                                                                                                                                                                                                                                          |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------- | ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 21/02/2024      | What is the OCB approach followed now for Renewals for families for all Insurers?                                                                                                                                                                                                                                                                                                                                                       | Alina         | Closed     | Currently, no OCB is present. Renewal plan is sent initially.<br>If alternate options are asked for, 1 within the same plan type, 2 downgraded plans & 2 upgraded plans are sent                                                                                                                                                                                                                                                     |
| 21/02/2024      | What is the OCB approach followed now for Renewals for families by Takaful Emarat?<br>Approach 1: Separate OCB emails are being sent to each family member<br>Approach 2: One long mail (with separate plans for each family member) is sent with all Renewal Policies and alternate options.                                                                                                                                           | Paula         | Closed     | Agatha: A single mail can be sent with 6 plans.<br>_All family members are included in the same plan (no separate plans are sent for each family member)_                                                                                                                                                                                                                                                                            |
| 21/02/2024      | Is there an updated Insurer Hierarchy doc?                                                                                                                                                                                                                                                                                                                                                                                              | Alina         | Closed     | Updated document not available.                                                                                                                                                                                                                                                                                                                                                                                                      |
| 21/02/2024      | Can we follow New Business logic for OCB Email sent for Renewals too?<br>Current Logic for NB:<br><br><br><br>FRD: Health One Click Buy (OCB) Logic ([https://doc.clickup.com/d/h/232ey-43247/38da9f24574d268](https://doc.clickup.com/d/h/232ey-43247/38da9f24574d268))<br><br>One-Click Apply Logic/Criteria ([https://doc.clickup.com/d/h/232ey-10987/ba69f84ecb00a9f](https://doc.clickup.com/d/h/232ey-10987/ba69f84ecb00a9f))<br> | Alina         | Open       | 21/02/2024: Logic should be based on Network Mapping.<br>Business will provide a network mapping document based on which logic will be developed for Renewals OCB Mail.<br><br>14/03/2024: Following the Network Mapping document presentation in the DT meeting, it was concluded that for Phase 1, the OCB email should include only the Renewal plan (with or without premium) to simplify the process and ensure a rapid launch. |
| 21/02/2024      | GMAX Renewal Reminder mail, should it be maintained?                                                                                                                                                                                                                                                                                                                                                                                    | Alina         | Open       | This has to be replaced with OCB Mail                                                                                                                                                                                                                                                                                                                                                                                                |
| 17/02/2024      | What are the skip scenarios in the case of Health?                                                                                                                                                                                                                                                                                                                                                                                      | Alina         | Open       | Business said it can be based on whether premium is available or not.                                                                                                                                                                                                                                                                                                                                                                |

## **Additional resources:**

Feature Scope Document: Private ([https://app.clickup.com/2197982/docs/232ey-43258/232ey-139258](https://app.clickup.com/2197982/docs/232ey-43258/232ey-139258))

RM / IMCRM Edit Quote: FRD: RM / IMCRM | Edit quote ([https://doc.clickup.com/d/h/232ey-26087/4cecb08bac43437/232ey-42007](https://doc.clickup.com/d/h/232ey-26087/4cecb08bac43437/232ey-42007))

Update Manual Plans: Private ([https://app.clickup.com/2197982/docs/232ey-28907/232ey-54047](https://app.clickup.com/2197982/docs/232ey-28907/232ey-54047))

RM / IMCRM Add Plan: FRD: RM / IMCRM | Add Plan ([https://doc.clickup.com/d/h/232ey-41087/ca36109270e7ea2](https://doc.clickup.com/d/h/232ey-41087/ca36109270e7ea2))

##

# FRD: Home Renewals CQF and OCB

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 06/02/2024 | 1.0         | Leesa           |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/865dax6ne](https://app.clickup.com/t/865dax6ne)) |
| ------------------------- | ------------------------------------------------------------------------------------ |
| **Sprint**                | \[Project manager to fill\]                                                          |
| **Business Requestors**   |                                                                                      |
| **Approvers**             |                                                                                      |
| **Budget Approval**       |                                                                                      |
| **CTO**                   | Hussain                                                                              |
| **CDTO**                  | Paula                                                                                |
| **CMO**                   | Hitesh                                                                               |
| **CPO - UI/UX**           |                                                                                      |
| **Content**               |                                                                                      |
| **PM**                    |                                                                                      |
| **BA/DTM/Champion**       | Leesa                                                                                |
| **QA**                    | \[Project manager to fill\]                                                          |
| **CPO**                   |                                                                                      |
| **Developers**            | \[Project manager to fill\]                                                          |
| **To Inform**             |                                                                                      |

## **A. User story: IMCRM Updates**

As the Renewals manager, I want to have a separate module for home upload and update so that the home upload process can be streamlined.

## **Requirements:**

- Introduce a permission called "Renewals-upload-nonmotor" any user with this permission will have access to non motor upload and update, uploaded leads, and batches.

1. A new submodule titled "Home Upload & Update" will be added under the "Renewals" module in IMCRM, as part of the existing "Non-Motor Upload & Update" section.

## ![](https://t2197982.p.clickup-attachments.com/t2197982/80dcf2f9-b6bb-4ffb-bb2d-7b3a97d45c93/image.png)

2\. Currently, we require the upload & update functions for both the Health and Home LOBs in this module, so the "Line of Business" filter can be prefilled with both "Health" and "Home."
Remaining features under this module should mirror the existing "Upload & Update" module.

## **B. User story: Upload and Update of Renewal Leads**

As Renewals manager, I want to be able to update the renewal leads with additional information so that leads are modified with renewal details.

## **Requirements:**

1. Attachment below shows the data fields that are to be entered in the upload & update template and displayed in IMCRM
2. The downloadable template will now be generated based on the selected Line of business (LOB) filter.

![](https://t2197982.p.clickup-attachments.com/t2197982/288603b0-b1ff-4b44-8ea2-8f59867457b1/image.png)
3\. Below is the sample xlsx file that should be available under the "Download Sample XLSX" button. This is when 'Home' is selected as the LOB.

[https://docs.google.com/spreadsheets/d/16GpHgUtncWkr4Zz-tOt8Irh3Y2TtxsjXgvXHrypfO8U/edit?gid=0#gid=0](https://docs.google.com/spreadsheets/d/16GpHgUtncWkr4Zz-tOt8Irh3Y2TtxsjXgvXHrypfO8U/edit?gid=0#gid=0)
4\. Separate xlxs. files will be provided for each LOB, containing specific fields tailored for different updates. These files can be downloaded for upload and update purposes.
5\. Renewal leads will be uploaded by Renewal manager through Upload and Update
6\. Template for upload and update fields with corresponding landing in IMCRM fields:
7\. There would be a new field added in IMCRM as additional notes
![](https://t2197982.p.clickup-attachments.com/t2197982/7183e6ec-a20a-4684-95d0-78d4390a8034/image.png)

| **Upload and update Fields**              | **IMCRM Fields**                                                                                               | **Additional Comments**            | **Required**    | **Notes**                                                                                                                   | **Max Size** |
| ----------------------------------------- | -------------------------------------------------------------------------------------------------------------- | ---------------------------------- | --------------- | --------------------------------------------------------------------------------------------------------------------------- | ------------ |
| Customer name                             | First name<br>Last name                                                                                        | (Customer profile )                | Yes             |                                                                                                                             | 100          |
| Customer email                            | Email                                                                                                          | (Customer profile )                | No              |                                                                                                                             | 255          |
| Customer number                           | Mobile number                                                                                                  | (Customer profile )                | No              |                                                                                                                             | 100          |
| Insurance type                            | Home                                                                                                           |                                    | Yes             |                                                                                                                             | 10           |
| Current insurance provider :-<br><br><br> | Currently Insured with                                                                                         | Quote details                      | Yes             |                                                                                                                             | 20           |
|                                           |
| Advisor Email                             | Advisor                                                                                                        | Quote details                      | Yes             |                                                                                                                             | 100          |
| Policy number                             | Previous Policy Number                                                                                         | Last year's policy details section | Yes             |                                                                                                                             | 100          |
| Policy start date                         | Previous Policy Start Date                                                                                     | Last year's policy details section | No              |                                                                                                                             | 10           |
| Policy End date                           | Previous Policy Expiry Date                                                                                    | Last year's policy details section | Yes             |                                                                                                                             | 10           |
| You are a                                 | Ownership status                                                                                               | Quote Details                      | Yes             |                                                                                                                             | 150          |
| I live in a (Type of property)            | Type of property                                                                                               | Quote Details                      | Yes             |                                                                                                                             | 25           |
| Occupancy status for owners               | Type of Owner's Occupancy                                                                                      | E-com detail (Anne)                | **Conditional** | It's only mandatory if ownership status is I'm a homeowner renting out my property                                          | 150          |
| Location area                             | Location area                                                                                                  | Customer profile                   | Yes             |                                                                                                                             | 100          |
| Cover required                            | Type of coverage you need                                                                                      | Quote details                      | Yes             |                                                                                                                             | 50           |
| Contents                                  | Contents AED                                                                                                   | Quote details                      | **Conditional** | <br><br><br>                                                                                                                | 50           |
| Personal Belongings                       | Personal belongings AED                                                                                        | Quote details                      | **Conditional** | <br><br><br>                                                                                                                | 25           |
| Building                                  | Building AED                                                                                                   | Quote details                      | **Conditional** | Only mandatory if a Ownership status is I'm a homeowner renting out my property and optional for the other ownership status | 25           |
| Insurance provider                        | Provider name                                                                                                  | Available plans                    | **Conditional** | Only mandatory if renewal premium is available                                                                              | 100          |
| Plan name                                 | Plan name                                                                                                      | Available plans                    | **Conditional** | Only mandatory if renewal premium is available                                                                              | 100          |
| Claims history                            | **Have you made any claims or experienced any losses in the past 5 years? \*(Anne will have to add in IMCRM)** | (Anne will add this field)         | Yes             |                                                                                                                             | 10           |
| Premium                                   | Renewal premium                                                                                                | Available plans                    | No              |                                                                                                                             | 20           |
| Previous advisor email                    | Previous advisor                                                                                               | Last Year's policy details         | No              |                                                                                                                             | 100          |
| Notes                                     | Additional notes<br>                                                                                           | Refer to the mockup                | No              |                                                                                                                             | 100          |

## **C. User story: Fetching of plans:**

As renewal manager, I want to fetch available plans so that alternate plans can be shared with customers if required.

## **Requirements:**

The existing logic used for health upload and update will be applied for fetching plans. No changes will be made to the current logic. The process will follow the established framework without modifications.
Refer to this block link:
[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-197538?block=block-de2923fe-8553-468c-8d24-c67cd542f577)

![](https://t2197982.p.clickup-attachments.com/t2197982/ce8030c4-a0fa-4fac-b02d-6d5be2cdc20f/image.png)

2\. When a specific year and month are selected (from the filter options), all home renewal batches with previous policy expiry date within the specified timeframe should be displayed.

1. Upon clicking the "Fetch Plans" button, alternate home insurance plans should be fetched and whatever renewal premium mentioned in the Upload and Update sheet should be incorporated with the lead. The premiums should be calculated based on the insured property's details and valuation at the time of renewal.
2. Remaining functionalities of the existing plan-fetching process should also be applied to home renewals.

## **D. User story : Triggering of OCB emails**

As a **renewal customer**, I want to automatically receive a One-Click Buy (OCB) email **30 days before my policy expiry**, so I can conveniently review and compare renewal rates and coverage options from various insurance providers, allowing me to make an informed decision with ease and efficiency.

## **Requirements :**

**Trigger Condition :**
The system should automatically generate and trigger an OCB email **30 days before the policy expiry date** for renewal leads.
**Applicability :**

- It applies to all leads with the lead source as **renewal_upload**.

**Email Routing Structure :**

| **OCB Renewals**  |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------- | --- |
| **From Email ID** | [advisor.username@notify.insurancemarket.ae](mailto:advisor.username@notify.insurancemarket.ae)                                    |
| **From Name**     | Advisor name                                                                                                                       |
| **To**            | <Customer email>                                                                                                                   |
| **Reply To**      | Advisor Email ID                                                                                                                   |
| **CC**            | Advisor's email address                                                                                                            |     |
| **BCC**           | [home@insurancemarket.ae](mailto:home@insurancemarket.ae), [newleadpool@insurancemarket.ae](mailto:newleadpool@insurancemarket.ae) |
| **Email Subject** | "\[Client's Name\]'s Home Insurance Renewal with Alfred \[REF ID\]"                                                                |
| **Preview Text:** |

- **From Email ID :** [advisor.username@notify.insurancemarket.ae](mailto:advisor.username@notify.insurancemarket.ae)
- **Frome name:** Advisor name
- **Reply-to:** Assigned advisor's email
- **To:** \[Client's email\]

**Email Content:**
![](https://t2197982.p.clickup-attachments.com/t2197982/5deae6ef-14b0-41ba-9466-4aa2b5411ae1/image.png)![](https://t2197982.p.clickup-attachments.com/t2197982/d12d37d8-efcf-4843-9577-3d7f2bcef9c2/image.png)
![](https://t2197982.p.clickup-attachments.com/t2197982/f785b366-005f-42e6-906b-51cd1dd322f8/image.png)![](https://t2197982.p.clickup-attachments.com/t2197982/2812d947-14af-43aa-bbe8-09ea31397bcb/image.png)

- **Email Subject Line:** "\[Client's Name\]'s Home Insurance Renewal with Alfred \[REF ID\]" | Anne Durwin's Home Insurance Renewal with Alfred HOM-JWYX9TYS
- **Attachment:** The email should include a PDF document with a detailed comparison of renewal options and coverage from various home insurance providers.
- The PDF content should be based on the renewal plans and premiums available for the specific policy.
- The Current insurer plan should have Renewal flag and should be first in the OCB email.
- ![](https://t2197982.p.clickup-attachments.com/t2197982/c2f35b5b-990a-4a86-922a-f516fac45670/image.png)

- IMCRM should also have the Renewal Flag in the available Plans.

![](https://t2197982.p.clickup-attachments.com/t2197982/aa637d10-25cc-4b49-bf46-a90667ab8bac/image.png)

- Once the OCB email is sent the lead status should be updated to Quoted.
- **Timing of Trigger:**
  - The OCB email will be prepared and triggered **30 days before the policy expiry date** to ensure the client has sufficient time to review and act on the renewal options(If it's Saturday coming on the day of OCB it should trigger on Friday instead of Saturday and if it's a Sunday then it should trigger on Monday).

OCB Content: [https://www.figma.com/design/rb4sZhnsUdh7ynsOUPR0oU/EMAILS?node-id=2043-10969&t=SzUzpXJKWhczNjI5-0](https://www.figma.com/design/rb4sZhnsUdh7ynsOUPR0oU/EMAILS?node-id=2043-10969&t=SzUzpXJKWhczNjI5-0)

**Additional Note:**
The assigned advisor's team will monitor these emails to ensure timely and professional communication with clients.

## **Low fidelity mock-ups:**

N/A

## **Assumptions:**

\[Write any assumptions you might have while writing the requirement but ideally most should be resolved at the time of FR approval\]

## **Open Questions:**

| **No.** | **Date Raised** | **Question/Concern** | **Raised by** | **Status** | **Outcome** |
| ------- | --------------- | -------------------- | ------------- | ---------- | ----------- |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |

## **Additional resources:**

\[List and add links to original BRD, ClickUp docs, ClickUp tasks, Google Sheets, Google Docs, email attachments in bullet format that are useful resources related to the requirement being documented\]

## **Use-case References:**

Below is one documents you can open and read through to gain an idea on how to start documenting the requirement. All above headers won't be found in the below samples as not all headers are required for each requirement.

Business Requirements: E-Commerce: Home Journey ([https://doc.clickup.com/d/h/232ey-36244/249ddfa80d86ab3/232ey-108244](https://doc.clickup.com/d/h/232ey-36244/249ddfa80d86ab3/232ey-108244))

# FRD: Automated Handling of Claims History for Insurance Renewals

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 24/06/2025 |             | Leesa Jeetwani  |              |

| **List or ClickUp tasks** | Email template content: Claims history Yes ([https://share.clickup.com/t/h/86ett9qqz/21QHGMJEMW5E105](https://share.clickup.com/t/h/86ett9qqz/21QHGMJEMW5E105)) |     |
| ------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| **Sprint**                | \[Project manager to fill\]                                                                                                                                     |     |
| **Business Requestor(s)** |                                                                                                                                                                 |     |
| **Approvers**             |                                                                                                                                                                 |     |
| **BA**                    |                                                                                                                                                                 |     |
| **QA**                    | \[Project manager to fill\]                                                                                                                                     |     |
| **Developers**            | \[Project manager to fill\]                                                                                                                                     |     |
| **To Inform**             |                                                                                                                                                                 |     |

##

**Background:**
In the context of home insurance renewals, it is essential to customize client communications based on their claims history. Clients with a history of claims require special handling to meet underwriting policies and manage risk appropriately. The current process involves verifying claims history through uploaded data in the CRM system and adjusting renewal communications accordingly, ensuring compliance and tailored service delivery. This user story outlines the automation of these tasks to enhance efficiency and accuracy in handling client renewals.

## **A. Use-case or User story:** Automated Claims History Verification for Tailored Insurance Renewal Communication

**As an IM user**, I want the system to check whether the "Claims History" field is set to "yes" in both the upload and update sheet. If it is, the system should set the "Claims History" field to "yes" in the IMCRM. This ensures that if a client has a "yes" in their claims history, we will not send plan cards alongside their renewal offer. Instead, they receive (OCB) without plan cards.

## **A. Requirements:**

**1.Field Verification:**

-       *   The system should verify the "Claims History" field in the upload and update sheet.
      *   If the "Claims History" field is "yes" or ''no", update the corresponding record in IMCRM to reflect this status.
      *   If "yes" then send OCB without plan cards and if ''no'' then send with plan cards.
      ![](https://t2197982.p.clickup-attachments.com/t2197982/2cb5a613-3bf5-413d-97c0-8d013ab023dc/image.png)
      ![](https://t2197982.p.clickup-attachments.com/t2197982/6a89da2a-7766-452f-a7ba-415dca108f2c/image.png)
  **2.Notification and Plan Card Handling:**
- If the "Claims History" is "Yes", the system should not fetch any plans; no API calls or PUA processes should be executed.
- Clients with "yes" in the claims history field should then trigger the 0 plan email template and the content is as follows:-

1. **Email Content:**
   - Subject: `Renew Your Home Insurance & Stay Secure!`
   - Body:

```plain
    Hi [Customer’s Name],


  Your home deserves the best protection—don’t let your current insurance lapse! With your renewal coming up, now’s the perfect time to lock in continued peace of mind.


  Here’s what you’re covered with:
    ● Policy Number(s): [Insert policy number(s)]
    ● Insurer: GIG GULF Insurance


  Why renew with us?
    ✔ 24/7 claims and support
    ✔ Tailored protection for your lifestyle
    ✔ Competitive pricing, no hidden fees


  Let’s make it easy—I'm [Advisor’s Name], and I’ll personally ensure a smooth, fast renewal.


  Reach out today and stay covered!
    Cheers,
    [Advisor’s Name]
    [Designation]
    [Mobile Number] | [Email Address]
    Happiness Center
```

Approved OCB design:

[

www.figma.com

https://www.figma.com/design/TveM2cfuRD5VLdQfhxWPVM/LOBs-New-Updates?node-id=112-5654&t=0yfR775NvxiiCIMU-0

](https://www.figma.com/design/TveM2cfuRD5VLdQfhxWPVM/LOBs-New-Updates?node-id=112-5654&t=0yfR775NvxiiCIMU-0)

##

# Home upload and update: Cover required for "I'm a homeowner renting out my property"

## **Meta Details:**

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86euay751](https://app.clickup.com/t/86euay751)) |
| ------------------------- | ------------------------------------------------------------------------------------ |
| **Approvers**             |                                                                                      |
| **BA**                    |                                                                                      |
| **Version Control**       | \[Maintained in CU Document History\]                                                |

Please delete sections that you don't use

## **Background:**

At the moment, when the ownership status is _I'm a homeowner renting out my property_ and the cover required is _Building and Contents_, it is throwing a validation error _Content is not required with Selected Ownership Status_. However, it is possible for a landlord to rent out his property with built-in content. Therefore, this validation in IMCRM should be removed and this particular scenario should be allowed.

## **Business Value Mapping**

\[**Every user story** must tie to a measurable value; e.g. “Reduces advisor manual work by X hrs/month”, “Expected to increase NPS by 10%”\]

| **KPI/Metric** | **Target/Description**                    | **Type**                          |
| -------------- | ----------------------------------------- | --------------------------------- |
| Retention rate | Accurate quotes to be provided to clients | Customer retention and experience |

## **MoSCoW Prioritization Table**

| **Requirement**                               | **Must/Should/Could/Won’t** | **Rationale (one line)**          |
| --------------------------------------------- | --------------------------- | --------------------------------- |
| \[A. User Story: Upload and update validation | Must                        | Customer retention and experience |

## **A. Use-case or User story: Upload and update validation**

As a Renewals Manager, I want to ensure that home quotes are accurate before sending out to the renewal client.

## **A. Requirements: Upload and update validation**

1. In the upload and update home template, if (You are a) _I'm a homeowner renting out my property_ and (Cover Required) _Building and Contents_ are selected together, then it should be allowed and no validation error _Content is not required with Selected Ownership Status_ should be thrown.

## **Additional resources:**

Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-221718](https://app.clickup.com/2197982/docs/232ey-46718/232ey-221718))

# Enhancement: Home Renewal OCB Email Update

## **Meta Details:**

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86evg5wte](https://app.clickup.com/t/86evg5wte)) |
| ------------------------- | ------------------------------------------------------------------------------------ |
| **Approvers**             |                                                                                      |
| **BA**                    | April                                                                                |
| **Version Control**       | \[Maintained in CU Document History\]                                                |

Please delete sections that you don't use

## **Background:**

Clients with multiple insured properties are unable to easily identify which specific property is due for renewal when viewing OCB emails. This lack of clarity can cause confusion and delays in renewal decisions. To resolve this, additional property details need to be displayed within the policy details card in the OCB communications.

## **A. Use-case or User story: Update the policy details**

**As a** customer receiving renewal emails,
**I want to** see the property type and location in the policy details section,
**so that** I can easily identify which property the renewal refers to and take prompt action.

## **A. Requirements: Update the policy details**

1. In the policy details card, add two more fields:
   1. Property type
   2. Property location
2. Mapping of fields as follows:

| OCB and follow-up emails | IMCRM fields - Section           |
| ------------------------ | -------------------------------- |
| Property type            | Type of property - Quote details |
| Property location        | Location area - Customer profile |

1. This update should be shown in the OCB email.

### **Low fidelity mock-up:**

![](https://t2197982.p.clickup-attachments.com/t2197982/e1dae657-3ff7-4aed-824d-fa80e16856d7/image.png)

[Home Renewal OCB Mobile.pdf](https://t2197982.p.clickup-attachments.com/t2197982/980711c3-4081-4e70-aa21-4107f2cb1f13/Home%20Renewal%20OCB%20Mobile.pdf)

[Home Renewal OCB Desktop.pdf](https://t2197982.p.clickup-attachments.com/t2197982/57826693-3596-4974-9851-2144d0f4c241/Home%20Renewal%20OCB%20Desktop.pdf)

## **Use-case References:**

Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-221718](https://app.clickup.com/2197982/docs/232ey-46718/232ey-221718))
Private ([https://app.clickup.com/2197982/docs/232ey-74638/232ey-232438](https://app.clickup.com/2197982/docs/232ey-74638/232ey-232438))

# FRD: Travel Renewals CQF and OCB - Automated

## **Version Table:**

| **Date** | **Version** | **Modified by** | **Comments** |
| -------- | ----------- | --------------- | ------------ |
| 14.07.24 | 1.0         | Anushya Ashok   |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/867809tea](https://app.clickup.com/t/867809tea)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   | Mahesh                                                                               |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   | Hussain                                                                              |     |
| **CDTO**                  | Paula                                                                                |     |
| **CMO**                   | Hitesh                                                                               |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Anushya                                                                              |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             | Ashmy                                                                                |     |

## **Background:**

This FRD outlines **the development of a new system within IMCRM to efficiently generate and send renewal quotes for existing leads prior to plan expiry.** This system will automate the auto-assignment of leads appropriately, and implement quoting logic to facilitate timely follow-ups. Additionally, it will integrate OCB (one-click buy) emails to streamline client communication and enhance the renewal process.

## **A. Use-case or User story: Renewal lead creation**

As IM, **automatically pick the renewal leads from existing leads 320 days before the current date** so that none of the renewals will be neglected. This automation ensures proactive renewal management and enhances client retention by sending timely quotes.

## **A. Requirements: Auto-renewal lead generation**

1. Pick leads that have the following conditions
   1. Travel start date that is 320 days old from the current date and
   2. Days cover 365 days or more and
   3. The lead status is 'Transaction approved' or 'policy booked' or payment status is captured /paid/ partially paid or credit approved\]
      1. then create a renewal lead with existing lead details.
   4. Set the plan expiry in the back end (Travel start date + Days covers (365)).
2. All the renewal leads will be categorized in a **"Renewal Batch"** based on the expiry month and year of the policy.
   - Create Batch Number: Use first 3 capital letters of the month, followed by the expiry year (MMMYYYY)
   - _For example: If Anushya Ashok's Policy is set to expire on 15th August 2024, she will fall into the batch AUG2024._
3. Set the lead source as 'renewal_upload'
4. During the lead generation, the system should **_duplicate the necessary client information_** from the existing lead to the new renewal lead _(differentiate the lead with Lead source and Ref ID)_
5. Link the new Ref ID with previous lead details page.
6. The auto-generated lead should have **complete customer profile details, travel details, member details and** **_customer-additional contacts (if any)_\*\***. No multiple customers - not creating a new customer** 1. **Customer Information\*\*
   (First Name, Last Name, Mobile Number and Email Id by default will be there in the renewal new lead)

| **Customer profile**       | **Required**       |
| -------------------------- | ------------------ |
| FIRST NAME                 | Yes                |
| LAST NAME                  | Yes                |
| INSURED FIRST NAME         | Yes                |
| INSURED LAST NAME          | Yes                |
| MOBILE NUMBER              | Yes                |
| EMAIL                      | Yes                |
| INSURED NATIONALITY        | Yes                |
| INSURED DATE OF BIRTH      | Yes                |
| INSURED EMIRATES ID NUMBER | If available, yes. |
| EMIRATES ID EXPIRY DATE    | If available, yes. |
| RISK CATEGORY              | No                 |
| UAE RESIDENT               | If available, yes. |

**Requirements:**

1.        1.     1. Marked 'Yes': Copy the data for the new renewal leads.
        2. Marked 'No': Don't copy
            1. Marked 'If available, yes': Copy the data only if the data is available.

b. **Lead Information -**
Policy Start date for new renewal lead = Previous expiry date +1
Policy Expiry date for new renewal lead **=** Start Date for new Renewal lead +365 days

If a policy expires before the customer renews it, the system should automatically set the new policy start date to the current date when the customer tries to access.
**Example:**
If Anushya's policy is set to expire on December 15th, 2025, and the renewal date is initially set to December 16th, 2025, but the customer fails to renew on time (by December 17th, 2025), the system should automatically adjust the new policy start date to December 17th, 2025 (the current date when customer tries to access).

(Travel details+ Member details+ Additional contacts (if any))
_Transfer member details only if the member details are provided in the lead._

| **Travel details**    | **Required**                      | **Member details** | **Required**       |
| --------------------- | --------------------------------- | ------------------ | ------------------ |
| REF ID                | Auto generate                     | FIRST NAME         | If available, yes. |
| CUSTOMER TYPE         | Yes                               | LAST NAME          | If available, yes. |
| ADVISOR               | Yes, to be assigned as use case B | NATIONALITY        | If available, yes. |
| CREATED DATE          | Auto generate                     | DATE OF BIRTH      | If available, yes. |
| LAST MODIFIED DATE    | No                                | GENDER             | If available, yes. |
| NEXT FOLLOWUP DATE    | No                                | RELATION           | If available, yes. |
| LOST REASON           | No                                | EMIRATES ID NUMBER | If available, yes. |
| NATIONALITY           | Yes                               | PASSPORT NUMBER    | If available, yes. |
| DESTINATION ID        | Yes                               | UAE RESIDENT       | If available, yes. |
| IS ECOMMERCE          | by default display, No            |                    |                    |
| MEMBERS               | Yes                               |                    |                    |
| DIRECTION CODE        | Yes                               |                    |                    |
| TRAVELING WHERE       | Yes                               |                    |                    |
| ARRIVED AT UAE        | No                                |                    |                    |
| DAYS COVERS           | Yes                               |                    |                    |
| TRAVEL START DATE     | No                                |                    |                    |
| TRAVEL END DATE       | No                                |                    |                    |
| TRAVEL COVERAGE       | Yes                               |                    |                    |
| REGION COVERAGE       | Yes                               |                    |                    |
| TRAVEL DESTINATION(S) | Yes                               |                    |                    |
| LEAD SOURCE           | Renewal_upload                    |                    |                    |

**Requirements:**

1.        1.     1. Marked 'Yes': Copy the data for the new renewal leads.
        2. Marked 'No': Don't copy
        3. Marked 'If available, yes': Copy the data only if the data is available.
        4. Auto generate: The system should **_autogenerate the Ref ID._**
        5. NA: Need not to copy the data for not applicable cases.
        6. By default show the lead source **_'Renewal upload'._**

## **B. Use-case or User story: Advisor assignment logic**

As IM, for every new lead generated for renewals, if the lead's previous journey involved an advisor, the system should instantly allocate the lead to a renewals advisor using a round-robin method.

## **B. Requirements: Assignment of advisor- for renewal leads**

1. **Trigger:** Lead status- New Lead \[with lead source: renewal upload\]
2. Considering batch created for a month set conditions as below :
   1. If one client's email ID is registered with multiple expiring policies, assign all the leads to the same advisor.
   2. The leads should be assigned to the advisors mapped to Travel - Renewals Team and role - Travel advisor on round robin basis.
3. The advisors can view the **'new renewal leads'** in the lead list with the lead status "**Allocated"** before sending a quote.

## **C. Use-case or User story: OCB quoting logic**

As IM, for each generated renewal lead, the system should automatically prepare a quote based on the client's existing policy details. The system will then send an OCB (one-click buy) email and WhatsApp to the client with the quotes, making it easy for the client to renew their policy with a few clicks. This automation ensures the timely delivery of quotes and simplifies the renewal process for clients.

## **C. Requirements: OCB quoting logic**

1. Automatically prepare a renewal quote for each generated renewal lead based on the client's existing details (Nationality, Gender, Date of birth, Type of plans Outbound and Travel coverage Annual, including UA/ Canada or excluding US/ Canada)
2. **Trigger: Lead status-Allocated**
3. The order of presenting the insurers is as follows:

| **Srl No** | **Insurer** | **Value**         |
| ---------- | ----------- | ----------------- |
| 1          | Alliance    | Lowest value plan |
| 2          | GIG         | Lowest value plan |
| 3          | Orient      | Lowest value plan |
| 4          | Union       | Lowest value plan |
| 5          | Al Watbha   | Lowest value plan |

For presenting plan cards follow the new business OCB Logic:[](https://app.clickup.com/2197982/docs/232ey-36925/232ey-122267?block=block-61fa62e1-0837-410e-999d-4850cbb7f1e5)

## **D. Use-case or User story: Send OCB Email**

As IM, the system should automatically generate and send an OCB (one-click buy) email to clients with their renewal quotes, making it easy for them to renew their policy with a few clicks. This automation ensures that clients receive their renewal quotes in a timely manner, enhancing their experience and increasing the likelihood of policy renewal.

## **D. Requirements: Send OCB Email**

| **Trigger**                                                                                                                                                                                                                                                                       | When the lead status is **'Allocated'**                                                                                                                                                                                                       |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Applies to**                                                                                                                                                                                                                                                                    | **Lead source:** renewal upload                                                                                                                                                                                                               |
| **Timing**                                                                                                                                                                                                                                                                        | Monday to Friday<br>09:00 am to 06:00 pm                                                                                                                                                                                                      |
| If 45 days prior to expiry date is a holiday, send the OCB on the very previous working day.<br><br>For example: If the policy is expiring on 27th Aug 2024,<br>45 days prior to 27th Aug 2024 is 28th July 2024(Sunday), So the previous working day is 26th July 2024 (Friday). |
| **Email header section- CQF OCB**                                                                                                                                                                                                                                                 | From: {Advisor email ID}<br>To: {Client email ID}<br>Reply-to: [travel.enquiries@insurancemarket.ae](mailto:travel.enquiries@insurancemarket.ae)<br>CC: [travel.enquiries@insurancemarket.ae](mailto:travel.enquiries@insurancemarket.ae)<br> |
| **Attachments**                                                                                                                                                                                                                                                                   | The email should include an attached PDF document (plans).                                                                                                                                                                                    |
| **Listing quotes**                                                                                                                                                                                                                                                                | Based on the client's enquiry show a maximum of 5 quotes.                                                                                                                                                                                     |
| **Client Replies**                                                                                                                                                                                                                                                                | Advisors should receive reply emails when the client responds to the OCB email.                                                                                                                                                               |

1.        1. **If there is one plan available**

| **OCB Email- With Advisor**                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------- |
| From                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | <Advisor Email ID>                                                                                       |
| To                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | <Client's email>                                                                                         |
| Reply to                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     | <[travel.enquiries@insurancemarket.ae](mailto:travel.enquiries@insurancemarket.ae) >                     |
| CC                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | <[travel.enquiries@insurancemarket.ae](mailto:travel.enquiries@insurancemarket.ae) >, <Advisor Email ID> |
| Email Subject                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                | {Client Name}'s Renewal Travel Insurance with Alfred {Ref ID}                                            |
| Email Content:                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Dear \[Client's Name\],<br><br>We hope this message finds you well.<br>As your dedicated advisor, I wanted to remind you that your travel insurance policy is up for renewal soon for:<br><br>Plan type: Annual multi-trip<br>Plan expiry: {dd/mmm/yyyy}<br><br>We have prepared personalised renewal quote for you.<br><br>**\[Quote 1\]**<br><br>We look forward having you covered throughout your amazing journeys!<br>We appreciate having you as our valued client and look forward to always remaining of service to you.<br><br>Feel free to reach out via Call, WhatsApp, or Email.<br><br>Best regards,<br>\[Advisor’s Name\]<br>\[Mobile number\] | \[Email Address\]<br>\[Happiness center\]                                                                |

1.        1. **If there are multiple plans available**

| **OCB Email- With Advisor**                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| From                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | <Advisor Email ID>                                                                                       |
| To                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | <Client's email>                                                                                         |
| Reply to                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     | <[travel.enquiries@insurancemarket.ae](mailto:travel.enquiries@insurancemarket.ae) >                     |
| CC                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | <[travel.enquiries@insurancemarket.ae](mailto:travel.enquiries@insurancemarket.ae) >, <Advisor Email ID> |
| Email Subject                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                | {Client Name}'s Renewal Travel Insurance with Alfred {Ref ID}                                            |
| Email Content:                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Dear \[Client's Name\],<br><br>We hope this message finds you well.<br>As your dedicated advisor, I wanted to remind you that your travel insurance policy is up for renewal soon for:<br><br>Plan type: Annual multi-trip<br>Plan expiry: {dd/mmm/yyyy}<br><br>We have prepared personalised renewal quotes for you.<br><br>**\[Quote 1\] ; \[Quote 2\] ; \[Quote 3\] ;**<br>**\[Quote 4\] ; \[Quote 5\]; \[Quote 6\]**<br><br>**\[See all your quotes\]**<br><br>We look forward having you covered throughout your amazing journeys!<br>We appreciate having you as our valued client and look forward to always remaining of service to you.<br><br>Feel free to reach out via Call, WhatsApp, or Email.<br><br>Best regards,<br>\[Advisor’s Name\]<br>\[Mobile number\] | \[Email Address\]<br>\[Happiness center\]                                                                |

1.        1. **For two travellers with 0-64 and 65 and above**

| **OCB Email- With Advisor**                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| From                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | <Advisor Email ID>                                                                                       |
| To                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             | <Client's email>                                                                                         |
| Reply to                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | <[travel.enquiries@insurancemarket.ae](mailto:travel.enquiries@insurancemarket.ae) >                     |
| CC                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             | <[travel.enquiries@insurancemarket.ae](mailto:travel.enquiries@insurancemarket.ae) >, <Advisor Email ID> |
| Email Subject                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | {Client Name}'s Renewal Travel Insurance with Alfred {Ref ID}                                            |
| Email Content:                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| Dear \[Client's Name\],<br><br>We hope this message finds you well.<br>As your dedicated advisor, I wanted to remind you that your travel insurance policy is up for renewal soon for:<br><br>Plan type: Annual multi-trip<br>Plan expiry: {dd/mmm/yyyy}<br><br>We have prepared personalised renewal quotes for you.<br><br>Plans for 1 traveller aged 0 to 64:<br>**\[Quote 1\] ; \[Quote 2\] ; \[Quote 3\]**<br><br>Plans for 1 traveller aged 65 and above:<br>**\[Quote 1\] ; \[Quote 2\] ; \[Quote 3\]**<br>**\[See all your quotes\]**<br><br>We look forward having you covered throughout your amazing journeys!<br>We appreciate having you as our valued client and look forward to always remaining of service to you.<br><br>Feel free to reach out via Call, WhatsApp, or Email.<br><br>Best regards,<br>\[Advisor’s Name\]<br>\[Mobile number\] | \[Email Address\]<br>\[Happiness center\]                                                                |

**Update on UI/UX: Request for UI/UX after getting the content approval.**

**PDF reference:** FRD: Generate PDF in Travel E-Commerce Select and Compare ([https://doc.clickup.com/d/h/232ey-31947/5f42a3db2a1cf38/232ey-94827](https://doc.clickup.com/d/h/232ey-31947/5f42a3db2a1cf38/232ey-94827))

## **Open Questions:**

| **No.** | **Date Raised** | **Question/Concern** | **Raised by** | **Status** | **Outcome** |
| ------- | --------------- | -------------------- | ------------- | ---------- | ----------- |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |

##

# Enhancement: Same advisor to be assigned for a mixed age group enquiry

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 30.12.2024 | 1.0         | Anushya Ashok   |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86er3ttcg](https://app.clickup.com/t/86er3ttcg)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Anushya Ashok                                                                        |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

## **A. Use-case or User story:**

As IM, I want to assign same advisor for a mixed age group enquiry so that the customers enquiring together have the same advisor.

## **A. Requirements:**

1. With the implementation ofPrivate ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-184518](https://app.clickup.com/2197982/docs/232ey-46718/232ey-184518)) , in case of advisor assignment logic do the following
   1. In case of a mixed age group enquiry, the same advisor should be assigned to all the members enquiring in the lead.
   2. As the mixed enquiry will have same email ID, it should not be considered as duplicate lead.

## **Open Questions:**

| **No.** | **Date Raised** | **Question/Concern** | **Raised by** | **Status** | **Outcome** |
| ------- | --------------- | -------------------- | ------------- | ---------- | ----------- |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |

# Enhancement: Travel CQF - Regions covered for quoting

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 17.01.2025 | 1.0         | Anushya Ashok   |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86er9m0pr](https://app.clickup.com/t/86er9m0pr)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Anushya Ashok                                                                        |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

Problem -
Currently, with Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-184518](https://app.clickup.com/2197982/docs/232ey-46718/232ey-184518)) to fetch plans the travel destination field is not populated hence OCB is not being sent.
![](https://t2197982.p.clickup-attachments.com/t2197982/e4c63ccb-a27a-42c8-909b-28420deb3fdd/image.png)

## **A. Use-case or User story:**

As IM, I want quotes to be generated for renewal leads considering regions covered as a parameter so that the plans can be sent out to the customer.

## **A. Requirements:**

1.  Use Travel regions field to map to country/ destination and generate quote.
    Refer to the sheets below for mapping
1.        1. [https://docs.google.com/spreadsheets/d/1SjDxlKb-E2b5JXw7IVFoAFvrD44svzEFeyeGZDdBjRo/edit?gid=1307441973#gid=1307441973](https://docs.google.com/spreadsheets/d/1SjDxlKb-E2b5JXw7IVFoAFvrD44svzEFeyeGZDdBjRo/edit?gid=1307441973#gid=1307441973)
    2. [https://docs.google.com/spreadsheets/d/18vxg_W-QtzGwoHUNHiDacPXE-WzcY58VWwZVNF8I_HA/edit?gid=998153355#gid=998153355](https://docs.google.com/spreadsheets/d/18vxg_W-QtzGwoHUNHiDacPXE-WzcY58VWwZVNF8I_HA/edit?gid=998153355#gid=998153355)

Example - The below lead shows the Travel region as Schengen Countries. This should be used to map to Travel countries provided in the table and generate quotes accordingly depending on the insurer.
![](https://t2197982.p.clickup-attachments.com/t2197982/920db042-a00e-416c-a2cb-f996bae79ff6/image.png)![](https://t2197982.p.clickup-attachments.com/t2197982/049a76b4-1cba-4771-8bb8-a7dda374ee94/image.png)
![](https://t2197982.p.clickup-attachments.com/t2197982/3d901e91-c744-42ce-9ca9-d265bfe47830/image.png)
2\. Quoting for older leads with no Travel destination should be with travel regions with the below countries mapped.

| **Travel regions**      | **Country** |
| ----------------------- | ----------- |
| Schengen                | Norway      |
| WW including US/ Canada | US          |
| WW excluding US         | France      |

## **Open Questions:**

| **No.** | **Date Raised** | **Question/Concern** | **Raised by** | **Status** | **Outcome** |
| ------- | --------------- | -------------------- | ------------- | ---------- | ----------- |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |
|         |                 |                      |               |            |             |

# FRD: Commercial Renewals CQF and OCB

## **Version Table:**

| **Date**    | **Version** | **Modified by** | **Comments** |
| ----------- | ----------- | --------------- | ------------ |
| 14 Nov 2024 | 1.0         | April           |              |
| 20 Nov 2024 | 1.1         | April           | <br><br>     |
| 21 Dec 2024 | 1.2         | April           | <br>         |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86enp4umh](https://app.clickup.com/t/86enp4umh)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestor(s)** |                                                                                      |     |
| **Approvers**             | Car Managers<br>Taimoor<br>Rahul<br>Hussain<br>Hitesh<br>Paula                       |     |
| **BA**                    | April Pascual                                                                        |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

##

## **Background:**

At the moment, we only trigger renewal reminders to our renewals that have company-registered vehicles as plans are not supported yet.

## **A. Use-case or User story: Upload and update template**

As a Renewals Manager, I want to ensure that I will be able to upload commercial renewals and send them OCB emails to increase the retention rate.

## **A. Requirements: Upload and update template**

1.  **Template Update:**
    1. Add the following columns to the existing upload and update template:
       1. **Registration Type**
          1. Mandatory field.
          2. Valid values: **"Company"** or **"Personal"**.
          3. Validation Rules:
             1. If no value is selected, throw an error: **"Registration Type is required."**
             2. If "Company" is selected, then:
                1. The customer name should be mapped to the company name on IMCRM.
                2. The **Vehicle Use** field must be filled.
             3. If "Personal" is selected, no changes to the existing flow and the existing retail APIs should run to generate car quotes.
                1. Retail APIs are the existing APIs we have for motor.
       2. **Vehicle Use**
          1. Mandatory field **only** when **Registration Type = Company**.
          2. Valid values: **"Commercial"** or **"Private"**.
          3. Validation Rules:
             1. If no value is selected and Registration Type = "Company," throw an error: **"Vehicle Use is required."**
             2. If **Vehicle Use = Commercial**:
                1. The **Business Activity** field becomes mandatory.
                2. Fields such as **Date of Birth**, **Driving Experience**, and **Nationality** become optional.
                3. The lead form must update dynamically to reflect these changes. Private ([https://app.clickup.com/2197982/docs/232ey-34384/232ey-200798](https://app.clickup.com/2197982/docs/232ey-34384/232ey-200798))
                4. Modify the **Fetch Plans** process to call the **Commercial API**.
             3. **If Vehicle Use = Private**:
                1. Fields such as **Date of Birth**, **Driving Experience**, and **Nationality** becomes required.
                2. The lead form must update dynamically to reflect these changes. Private ([https://app.clickup.com/2197982/docs/232ey-34384/232ey-200798](https://app.clickup.com/2197982/docs/232ey-34384/232ey-200798))
                3. Modify the **Fetch Plans** process to call the **Retail APIs**.
       3. **Business Activity**
          1. Mandatory field **only** when **Vehicle Use = Commercial**.
          2. **Validation Rules**:
             1. If no value is selected, throw an error: **"Business Activity is required."**
             2. The Business Activity selection must be based on the [**Level 3 code**](https://docs.google.com/spreadsheets/d/1QaeXZV5u-SD9JNJp_6K3-lSbPm7P-OR0kbcZbrnnRkA/edit?gid=1641152362#gid=1641152362&range=F:F) for accurate categorization.
    2. This should be both applicable whether the SIC toggle is on or off.
2.  **Email Content Adjustments:** 1. **Content:** Keep the existing renewal OCB email and follow-up email content unchanged. 2. **Dynamic Fields:** Ensure the following fields are dynamically populated in the email: 1. Client's name
    ![](https://t2197982.p.clickup-attachments.com/t2197982/61218d55-881e-40d9-9f11-3c31efb680c5/image.png)
3.        1.     1. Advisor's name
    ![](https://t2197982.p.clickup-attachments.com/t2197982/367b5077-8951-4654-811a-3e89e1510468/image.png)
4.        1.     1. Vehicle
            2. Current insurer
            3. Policy number
            4. Renewal due date
    ![](https://t2197982.p.clickup-attachments.com/t2197982/1c9a7731-0765-4b93-b8e3-a541076291fc/image.png)
5.        1.     1. Advisor card details
    ![](https://t2197982.p.clickup-attachments.com/t2197982/4bb88c1c-2a13-46ce-a2a3-fcdd6e09bfcb/image.png)
6.        1.     1. Signature
    ![](https://t2197982.p.clickup-attachments.com/t2197982/e47efa5b-e27d-4bc2-8654-cd133e0ce277/image.png)
7.        1. Update the email header (Your Company Car Insurance Renewal) and image (shown below)[](https://app.clickup.com/2197982/docs/232ey-34384/232ey-182058?block=block-1fc60f3e-9f15-4492-9a3d-98c65a29a269)

![](https://t2197982.p.clickup-attachments.com/t2197982/095824cf-fc96-4011-9ea4-3b6179272551/image%20-%202024-12-21T130632.200.png)

3.  **Standard Flow:**
1.        1. If **Registration Type = Personal**, continue with the existing flow without changes and the existing retail APIs should run to generate car quotes.

[https://docs.google.com/spreadsheets/d/1OSYVIEoxj7vr-FumZZBVzY2Xj18I4VDAAKKQGl8LEVo/edit?gid=0#gid=0](https://docs.google.com/spreadsheets/d/1OSYVIEoxj7vr-FumZZBVzY2Xj18I4VDAAKKQGl8LEVo/edit?gid=0#gid=0)

## **B. Use-case or User story: Motor retention report filter**

As IM, I want to view the retention report for private and commercial vehicles separately to better analyze performance based on vehicle use.

## **B. Requirements: Motor retention report filter**

1. **Filter Addition:**
   1. Add the following filters to the **Motor Retention Report**:
      1. **Registration type:**
         1. Dropdown with values:
            1. Personal
            2. Company
         2. Default Behavior:
            1. Allow users to view all data if no filter is selected.
      2. **Vehicle Use**:
         1. Visible **only** when **Registration Type = Company**.
         2. Dropdown with values:
            1. Private
            2. Commercial
         3. Default Behavior:
            1. Allow users to view all data if no filter is selected.
2. **Report Behavior:**
   1. **Filter Logic**:
      1. If **Registration Type = Personal**, display only leads where:
         1. Registration Type = Personal.
      2. If **Registration Type = Company** and **Vehicle Use = Private**, display only leads where:
         1. Registration Type = Company
         2. Vehicle Use = Private
      3. If **Registration Type = Company** and **Vehicle Use = Commercial**, display only leads where:
         1. Registration Type = Company
         2. Vehicle Use = Commercial
      4. **Default View**: If no filters are applied, display all leads regardless of registration type or vehicle use.

## **Open Questions:**

| **No.** | **Date Raised** | **Question/Concern**                                                               | **Raised by** | **Status** | **Outcome**                     |
| ------- | --------------- | ---------------------------------------------------------------------------------- | ------------- | ---------- | ------------------------------- |
| 1       | 14 Nov 2024     | Do we consider all company registered renewals to be under vehicle use commercial? | April         | Closed     | Based on the business activity. |

## **Additional resources:**

Private ([https://app.clickup.com/2197982/docs/232ey-34384/232ey-200798](https://app.clickup.com/2197982/docs/232ey-34384/232ey-200798))

[

docs.google.com

https://docs.google.com/spreadsheets/d/1OSYVIEoxj7vr-FumZZBVzY2Xj18I4VDAAKKQGl8LEVo/edit?gid=0#gid=0

](https://docs.google.com/spreadsheets/d/1OSYVIEoxj7vr-FumZZBVzY2Xj18I4VDAAKKQGl8LEVo/edit?gid=0#gid=0)

[

docs.google.com

https://docs.google.com/spreadsheets/d/1QaeXZV5u-SD9JNJp\_6K3-lSbPm7P-OR0kbcZbrnnRkA/edit?gid=1641152362#gid=1641152362

](https://docs.google.com/spreadsheets/d/1QaeXZV5u-SD9JNJp_6K3-lSbPm7P-OR0kbcZbrnnRkA/edit?gid=1641152362#gid=1641152362)

# OCB Renewal Email - Personal

Registration type: Personal

Akash Pal's Car Insurance with Alfred

from: advisor's email ID (@renewals.insurancemarket.ae)
reply-to: advisor's email ID
to: client's email ID
cc: advisor's email ID

Dear Akash Pal,

How time flies! It's already time to renew your motor insurance policy.

I’m Wasim Sayed, your dedicated insurance advisor, here to assist you in securing the right plan for your car insurance.

![](https://t2197982.p.clickup-attachments.com/t2197982/0648d3cb-2cf3-4440-bcb4-b20f3f36e71b/image.png)

# OCB Renewal Email - Company

Registration type: Company

ABC Company's Car Insurance with Alfred

from: advisor's email ID (@renewals.insurancemarket.ae)
reply-to: advisor's email ID
to: client's email ID
cc: advisor's email ID

Dear Sir/Madam,

How time flies! It's already time to renew your motor insurance policy.

I’m Wasim Sayed, your dedicated insurance advisor, here to assist you in securing the right plan for your car insurance.

![](https://t2197982.p.clickup-attachments.com/t2197982/91c1bd35-c296-4098-940f-133fcb268716/image.png)

# Enhancement: Commercial OCB: Renewal plan card placement should be first

## **Version Table:**

| **Date**     | **Version** | **Modified by** | **Comments** |
| ------------ | ----------- | --------------- | ------------ |
| 17 June 2025 | 1.0         | April           | Enhancement  |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86ettpqbz](https://app.clickup.com/t/86ettpqbz)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestor(s)** |                                                                                      |     |
| **Approvers**             | Paula                                                                                |     |
| **BA**                    | April                                                                                |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

## **Background:**

At the moment, commercial OCB plan cards are arranged from lowest to highest. However, the renewal plan card should be first, then lowest to highest to mirror the existing OCB email for individual customers.

## **A. Use-case or User story:**

As IM, I want the renewal plan card to appear first in the OCB email, so that the client sees their renewal quote upfront. This helps us guide their decision and supports the retention targets we have with our insurer partners.

## **A. Requirements:**

1. Renewal plan card should be displayed first in the OCB email, then followed by the logic in this FRD Private ([https://app.clickup.com/2197982/docs/232ey-65518/232ey-202438](https://app.clickup.com/2197982/docs/232ey-65518/232ey-202438))

## **Additional resources:**

Private ([https://app.clickup.com/t/86eqqwmk2](https://app.clickup.com/t/86eqqwmk2))
Private ([https://app.clickup.com/2197982/docs/232ey-65518/232ey-202438](https://app.clickup.com/2197982/docs/232ey-65518/232ey-202438))

# FRD: Bike Renewals CQF and OCB

## **Meta Details:**

| **FRD Name**         | Bike Renewals CQF and OCB | **List or ClickUp tasks** | Private ([https://app.clickup.com/t/85zu2yxtt](https://app.clickup.com/t/85zu2yxtt)) |
| -------------------- | ------------------------- | ------------------------- | ------------------------------------------------------------------------------------ |
| **Prepared by**      | April Pascual             | **Designation**           | DT Manager                                                                           |
| **Reviewed by**      | Mohammad Asad Alam        | **Designation**           | Head of Product and CX                                                               |
| **Reviewed date**    | 24.12.2025                | **Status**                | Approved                                                                             |
| **Business Review**  |                           | **Designation**           |                                                                                      |
| **Reviewed Date**    |                           | **Status**                |                                                                                      |
| **Level 1 Approver** | Hitesh Motwani            | **Designation**           | Deputy CEO                                                                           |
| **L1 Approval Date** | TBC                       | **Status**                |                                                                                      |
| **Level 2 Approver** | Avinash Babur             | **Designation**           | CEO                                                                                  |
| **L2 Approval Date** | TBC                       | **Status**                |                                                                                      |
| **Version Control**  | 1.0                       | **Status**                | WIP                                                                                  |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/85zu2yxtt](https://app.clickup.com/t/85zu2yxtt)) |
| ------------------------- | ------------------------------------------------------------------------------------ |
| **Approvers**             |                                                                                      |
| **BA**                    | April Pascual                                                                        |
| **Version Control**       | \[Maintained in CU Document History\]                                                |

Please delete sections that you don't use

## **Background\*\***:\*\*

Currently, bike renewals are being created under the car quotes in IMCRM, leading to inaccurate classification and reporting. This enhancement is required to handle Bike renewals separately under the correct line of business (bike quotes), ensuring accurate lead management and data integrity.

This FRD extends existing renewal capabilities to the Bike line of business under Non-Motor, enabling the following features:

- Upload and update Bike renewal leads
- Fetch available Bike insurance plans
- Send renewal emails in bulk via the OCB process

## **Business Value Mapping**

\[**Every user story** must tie to a measurable value; e.g. “Reduces advisor manual work by X hrs/month”, “Expected to increase NPS by 10%”\]

| **KPI/Metric**                       | **Target/Description**                                                                       | **Type**                   |
| ------------------------------------ | -------------------------------------------------------------------------------------------- | -------------------------- |
| Correct LOB classification           | Achieve 100% accurate classification of bike renewals under Bike quotes                      | Data Quality               |
| Renewal quote accuracy               | Achieve 100% consistency in premiums, add-ons, and flags across IMCRM, e-com, and OCB emails | Quality                    |
| OCB Email Automation Rate            | Ensure 100% of eligible leads automatically triggered for OCB emails when conditions are met | Efficiency / Automation    |
| Compliance with Payment Status Rules | 100% of leads with Paid, Authorised, or Partially Paid status excluded from email send       | Customer Experience        |
| Failure Notification Timeliness      | Internal email notifications triggered within 30 minutes of any failed batch                 | Operational Responsiveness |

## **MoSCoW Prioritization Table**

| **Requirement**                             | **Must/Should/Could/Won’t** | **Rationale (one line)**                                          |
| ------------------------------------------- | --------------------------- | ----------------------------------------------------------------- |
| Enable Upload & Update for Bike quotes      | Must                        | Core functionality to bring Bike renewals in line with Motor/Home |
| Fetch alternate Bike insurance plans        | Must                        | Enables competitive quote generation                              |
| Send Emails button under Non-Motor for Bike | Must                        | Enables batch communication                                       |

## **A. Use-case or User story: Upload and create**

**As a** Renewals Manager,
**I want to** upload and create bike renewal leads in bulk using the existing upload and create template and mapping rules,
**so that** bike renewal leads can be managed efficiently in the system.

## **A. Requirements: Upload and create**

1. Enable upload and create for bike quotes.
2. Use the existing upload and create template along with its validations.
3. Bike EP should continue working as how it is for bike new business leads Private ([https://app.clickup.com/t/86epywn6y](https://app.clickup.com/t/86epywn6y))

## **B. Use-case or User story: Upload and update**

**As a** Renewals Manager,
**I want to** upload and update bike renewal leads in bulk using the existing motor template and mapping rules,
**so that** bike quote details can be managed efficiently in the system, keeping data consistent and enabling smooth renewal handling without triggering client emails.

## **B. Requirements: Upload and update**

1. This is only for the role Renewals_manager.
2. Enable upload and update for bike quotes.
3. Use the existing [Motor upload and update template](https://docs.google.com/spreadsheets/d/1kHRDzLcamn4wd5K2Ce2eko2Bim0-tnqwKSTRDXE04yo/edit?gid=0#gid=0) (with updated validations).
4. If the Insurance Type is BIK, then use the mapping and validation as per the [table below](https://app.clickup.com/2197982/docs/232ey-46718/232ey-316038?block=block-ffe5505f-be7c-4126-818f-a0c9f20ea5c3):
   1. No changes to the existing validations if the Insurance Type mentioned in the upload and update file is CAR.
5. Under _Non-motor Upload and Update_, add Bike from the dropdown values of Line of Business selection.
6. _Uploaded Leads_ should show the details of the upload - same as how it is happening for motor and home.
7. The downloadable template should be updated as per [below fields and conditions](https://app.clickup.com/2197982/docs/232ey-46718/232ey-316038?block=block-ffe5505f-be7c-4126-818f-a0c9f20ea5c3) if Bike is selected as the Line of business.
8. If the renewal lead is already assigned to an advisor, the lead should ignore the advisor email mentioned in the upload and update sheet.
9. No intro email should be sent to the client at this point.

| Upload and update column      | IMCRM mapping                                   | Description                                                                                                                                                                            | Validation                                                      | Max Size |
| ----------------------------- | ----------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- | -------- |
| Customer Name                 | First Name and Last Name                        | Split the customer name to first name and last name based on existing IMCRM validations                                                                                                | Optional                                                        | 100      |
| Customer E-mail               | Email                                           | Should be a valid email ID                                                                                                                                                             | Optional                                                        | 50       |
| Customer Mobile               | Mobile number                                   | Should only accept numeric values                                                                                                                                                      | Optional                                                        | 50       |
| Insurance Type                | BIK                                             | If BIK, then check leads under bike quotes                                                                                                                                             | Required                                                        | 10       |
| Insurance Provider            | Currently Insured With                          | Allow only values from [insurance provider code](https://imcms.alfred.ae/cms/insurance-provider) in CMS                                                                                | Required                                                        | 10       |
| Registration Type             | N/A                                             | N/A                                                                                                                                                                                    | Not required if Insurance Type is BIK                           |          |
| Vehicle Use                   | N/A                                             | N/A                                                                                                                                                                                    | Not required if Insurance Type is BIK                           |          |
| Business Activity             | N/A                                             | N/A                                                                                                                                                                                    | Not required if Insurance Type is BIK                           |          |
| Driver Name                   | N/A                                             | N/A                                                                                                                                                                                    | Not required if Insurance Type is BIK                           |          |
| Product Type                  | Bike Insurance                                  | Should match with the Insurance Type BIK                                                                                                                                               | Required                                                        | 50       |
| Advisor Email                 | Advisor                                         | Accept only valid IMCRM users                                                                                                                                                          | Required if SIC toggle is off                                   | 50       |
| Policy Number                 | Previous Policy Number                          | Use this to validate which lead to be updated along with the Previous Policy Expiry Date                                                                                               | Required                                                        | 100      |
| Policy End Date               | Previous Policy Expiry Date                     | Use this to validate which lead to be updated and format should be DD/MM/YYYY                                                                                                          | Required                                                        | 10       |
| Batch                         | Renewal Batch                                   | Should be assigned based on the Previous Policy Expiry Date and which [non-motor renewal batch](https://imcrm.alfred.ae/generic/renewal-batches?page=1&quote_type_id=-1) it falls into | System assigned                                                 | 10       |
| Car Make                      | Bike Make                                       | Allow only bike makes in IMCRM                                                                                                                                                         | Required                                                        | 50       |
| Car Model                     | Bike Model                                      | Allow only bike models in IMCRM                                                                                                                                                        | Required                                                        | 50       |
| Model Year                    | Bike Model Year                                 | Allow only values from [Year of Manufactures](https://imcms.alfred.ae/cms/year-of-manufacture) in CMS                                                                                  | Required                                                        | 50       |
| Date of Birth                 | Date of Birth                                   | Format should be DD/MM/YYYY                                                                                                                                                            | Required                                                        | 10       |
| Driving Experience            | UAE License Held For                            | Allow only values from UAE Licence Held For in IMCRM                                                                                                                                   | Required                                                        | 50       |
| Nationality                   | Nationality                                     | Allow only values from [Nationalities](https://imcms.alfred.ae/cms/nationality) in CMS                                                                                                 | Required                                                        | 50       |
| Provider Name                 | Provider Name                                   | Allow only insurance provider code and should match with the value under Insurance Provider column                                                                                     | Conditional - only required if the Renewal Premium is available | 10       |
| Plan Name                     | Plan Name                                       | Allow only plan names in CMS with quote type Bike and should be under the insurance provider code selected                                                                             | Conditional - only required if the Renewal Premium is available | 50       |
| Repair Type                   | Repair Type                                     | Based on the plan selected                                                                                                                                                             | Conditional - only required if the Renewal Premium is available | 50       |
| Claims History                | Claim History                                   | Allow only values from Claim History in IMCRM                                                                                                                                          | Required                                                        | 50       |
| NC Letter                     | No-claims Letter                                | Accept only Yes or No                                                                                                                                                                  | Required                                                        | 10       |
| Insurer Quote No.             | Insurer Quote No.                               | Should accept alphanumeric values                                                                                                                                                      | Conditional - only required if the Renewal Premium is available | 50       |
| Car Value (From Insurer)      | Bike Value                                      | Should only accept numeric values                                                                                                                                                      | Required                                                        | 50       |
| Renewal Premium               | Actual Price                                    | Should only accept numeric values                                                                                                                                                      | Optional                                                        | 10       |
| Excess                        | Excess                                          | Excess should be 0 if the repair type is TPL, and should be more than 0 if COMP or AGENCY                                                                                              | Conditional - only required if the Renewal Premium is available | 10       |
| Ancillary Excess              | Ancillary Excess                                | Input as a whole number but show as a percentage                                                                                                                                       | Optional                                                        | 10       |
| PAB Driver                    | N/A                                             | N/A                                                                                                                                                                                    | Optional                                                        | 50       |
| Amount - PAB Driver           | N/A                                             | N/A                                                                                                                                                                                    | Conditional - only required if PAB Driveris selected            | 10       |
| PAB Passenger                 | Passenger Cover                                 | Based on the plan selected                                                                                                                                                             | Optional                                                        | 50       |
| Amount - PAB Passenger        | Passenger Cover Amount                          | Should only accept numeric values                                                                                                                                                      | Conditional - only required if PAB Passenger is selected        | 10       |
| Rent a car                    | N/A                                             | N/A                                                                                                                                                                                    | Not required if Insurance Type is BIK                           |          |
| Amount- Rent a Car            | N/A                                             | N/A                                                                                                                                                                                    | Not required if Insurance Type is BIK                           |          |
| Oman Cover                    | Oman Cover                                      | Based on the plan selected                                                                                                                                                             | Optional                                                        | 50       |
| Amount - Oman Cover           | Oman Cover Amount                               | Should only accept numeric values                                                                                                                                                      | Conditional - only required if Oman Cover is selected           | 10       |
| Road Side Assistance          | Roadside Assistance                             | Based on the plan selected                                                                                                                                                             | Optional                                                        | 50       |
| Amount - Road Side Assistance | Roadside Assistance Amount                      | Should only accept numeric values                                                                                                                                                      | Conditional - only required if Roadside Assistance is selected  | 10       |
| First Year of Registration    | Year Of First Registration                      | Must not be more than 1 plus and minus the bike model year                                                                                                                             | Required                                                        | 10       |
| Trim                          | No mapping                                      | Freeform                                                                                                                                                                               | Optional                                                        | 50       |
| Registration Location         | Emirate of Registration                         | Allow only values from [Emirates](https://imcms.alfred.ae/cms/emirate) in CMS                                                                                                          | Required                                                        | 50       |
| Previous Advisor Email        | Previous Advisor                                | Accept only valid IMCRM users                                                                                                                                                          | Optional                                                        | 50       |
| Notes                         | Introduce a new notes field under quote details | Freeform                                                                                                                                                                               | Optional                                                        | 100      |
| Is GCC                        | Is GCC Standard                                 | Accept only Yes or No                                                                                                                                                                  | Required                                                        | 10       |

##

## **C. Use-case or User story: Fetch plans**

**As a** Renewals Manager,
**I want to** fetch alternate bike insurance plans for a selected renewal batch or date range,
**so that** updated renewal premiums, add-ons, and renewal flags are automatically applied to each lead while ensuring paid or authorised policies remain unchanged.

## **C. Requirements: Fetch plans**

1. Under _Non-motor Batches_, add Bike as a dropdown value of Line of business.
2. Follow the existing system conditions. For reference:[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-221718?block=block-91d6990f-0c1c-4774-ade0-b6d4c09fae1f)
3. When a specific year and month, or a specific renewal batch is selected (from the filter options), batches should be shown accordingly.
4. Upon clicking the "Fetch Plans" button, alternate bike insurance plans should be fetched and the renewal premium and add-ons mentioned in the Upload and Update sheet should be incorporated to the lead.
5. Add a renewal flag and manual flag at plan level based on the plan name mentioned in the upload and update sheet.
6. The renewal flag should be visible in IMCRM, e-com, and OCB emails, while the manual flag remains visible only in IMCRM.
7. If the payment status is Authorised, Paid, or Partially Paid, then:
   1. Do not update the renewal plan
   2. Do not update any manual plan
   3. Do not fetch other plans
8. Use the same check for Bike EP renewals and should be pre-selected if the lead falls within the following criteria mentioned in this FRD: Private ([https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098](https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098))
9. Point 8 will not be required once the automation is in place[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284598?block=block-fa24a1a9-54e6-48a8-88cd-284523611940)

## **D. Use-case or User story: Send Emails**

**As a** Renewals Manager,
**I want to** trigger OCB renewal emails for bike batches directly from the Non-Motor Batches screen,
**so that** renewal leads move to the 'Quoted' stage and email communication is logged automatically, while excluding leads with paid or authorised statuses.

## **D. Requirements: Send Emails**

1.  The system should automatically trigger [Renewal OCB emails](https://app.clickup.com/2197982/docs/232ey-46718/232ey-316858) when all of the following conditions are met:
    1. Upload and Update is already processed, ensuring all required information for quote generation is available.
    2. Fetch Plans process is completed to confirm quotes have been generated.
    3. The previous policy expiry date is 50 days from the current date (exact trigger time to be confirmed).
       1. If the 50th day falls on a Saturday or Sunday, the email should be triggered on the next working day (Monday).
    4. Payment status is **not** Authorised, Paid, or Partially Paid. If any of these statuses apply, the OCB email should **not** be triggered for that lead.
2.  If sending the OCB emails fails, an [internal email notification](https://app.clickup.com/2197982/docs/232ey-46718/232ey-331058) should be triggered within 30 minutes of the failed batch, containing the following details:
    1. Date of attempt
    2. Number of total leads
    3. Number of total emails sent
    4. Number of total emails failed
    5. Reasons for email failure - can be in a form of excel file if there are multiple failed emails
3.  Once the OCB email is successfully sent: 1. The lead status should be updated to **Quoted**. 2. The action should be logged in the **Email Status section** of the renewal lead.
    ![](https://t2197982.p.clickup-attachments.com/t2197982/9dd13cf7-cf69-4f86-b6dc-375f605961a5/image.png)
4.  For Non-Motor Batches, if the Line of Business (LOB) is Bike, display a **Send Emails** button.
    ![](https://t2197982.p.clickup-attachments.com/t2197982/06de8008-330f-44c6-a82e-f023285c6ee5/image.png)
5.        1. Upon clicking **Send Emails**:
            1. Open a new page showing **Email Batch Details**.
            2. Any email failures from this batch can be **retriggered** from this page.
    ![](https://t2197982.p.clickup-attachments.com/t2197982/9e186220-0688-4e19-aeb9-ca48bec13df2/image.png)

## **D. Use-case or User story: Send WhatsApp**

**As a** Renewals Manager,
**I want to** trigger OCB renewal WhatsApp for bike renewal leads,
**so that**

## **D. Requirements: Send WhatsApp**

1.

## **Open Questions:**

| **No.** | **Date Raised** | **Question/Concern**                                                                                        | **Raised by** | **Status** | **Outcome**                       |
| ------- | --------------- | ----------------------------------------------------------------------------------------------------------- | ------------- | ---------- | --------------------------------- |
| 1       | 21 Oct 2025     | SIC for bike renewals?                                                                                      | April         | Closed     | No as per HM on 12 Dec 2025       |
| 2       | 29 Oct 2025     | Check with Jerin if there's any impact with the allocation logic if SIC needs to be done for renewal leads. | April         | Closed     | No SIC flow for bike as per Jerin |
| 3       | 24 Dec 2025     | What about renewal follow-up emails?                                                                        | April         | Closed     | Will be taken up separately       |
| 4       | 24 Dec 2025     | What about Tier R redirection?                                                                              | April         | Closed     | Will be taken up separately       |
| 5       | 29 Dec 2025     | WA Template and Status                                                                                      | Amit          | Open       | Should be included in this FRD    |

## **Additional resources:**

Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-221718](https://app.clickup.com/2197982/docs/232ey-46718/232ey-221718))
Private ([https://app.clickup.com/2197982/docs/232ey-52058/232ey-164038](https://app.clickup.com/2197982/docs/232ey-52058/232ey-164038))
Private ([https://app.clickup.com/t/86epywn6y](https://app.clickup.com/t/86epywn6y))
Private ([https://app.clickup.com/t/86eqf0kxx](https://app.clickup.com/t/86eqf0kxx))
Private ([https://app.clickup.com/t/86erjcymf](https://app.clickup.com/t/86erjcymf))
Private ([https://app.clickup.com/2197982/docs/232ey-66718/232ey-204998](https://app.clickup.com/2197982/docs/232ey-66718/232ey-204998))

# Renewal OCB Email for Bike (Non-SIC)

| **OCB Renewal Email** |
| --------------------- | ----------------------------------------------------------------------------- |
| **From Email ID**     | [advisor@notify.insurancemarket.ae](mailto:advisor@notify.insurancemarket.ae) |
| **From Name**         | Advisor's name                                                                |
| **To**                | Customer's email ID                                                           |
| **Reply To**          | Advisor's email ID                                                            |
| **CC**                | Advisor's email ID                                                            |
| **BCC**               |                                                                               |
| **Email Subject**     | "\[Client's Name\]'s Bike Insurance Renewal with Alfred"                      |
| **Preview Text**      | "\[Client's Name\]'s Bike Insurance Renewal with Alfred"                      |

Title: Your Bike Insurance Renewal

Email body:

Dear \[Client's Name\],

How time flies! It is already time to renew your bike insurance policy.

I am \[Advisor's Name\], your dedicated insurance advisor, here to assist you in securing the right plan for your bike insurance.

Bike: \[Bike make, model, model year\]
Current insurer: \[Currently insured with\]
Policy number: \[Previous policy number\]
Policy expiry date: \[Previous policy expiry date\]

Insert advisor card

Insert plan cards

Insert USPs

Your bike registration may expire a month before insurance. It is advisable to renew promptly to avoid traffic fines.

Thank you for choosing [InsuranceMarket.ae](http://InsuranceMarket.ae) for your insurance needs. We appreciate having you as our valued client and look forward to always remaining of service to you.

Feel free to reach out via Call, WhatsApp or Email. (Should be hyperlinks)

Best regards,

Advisor Name
Advisor's mobile number | Advisor's email ID
Happiness Centre: 800 ALFRED (800 253 733)

![](https://t2197982.p.clickup-attachments.com/t2197982/464964cf-9678-4706-aaa5-cf2f35977ef2/image.png)

**Mock-up:**
![](https://t2197982.p.clickup-attachments.com/t2197982/e1fd266a-f100-499d-9f63-435b37e57a7e/image.png)

Kunal:

1. Remove contractions
2. Suggestion is to keep two sections in the plan card
   1. Renewal
   2. Other options
3. USPs are not aligned
4. Sender, signature? IM or advisor?
5. Advisor card details - context required before the displaying the card

Amit:

1. 2 templates
   1. With renewal
      1. Show details of incumbent
      2. Other quotes, make it minimal info
   2. Without renewal (mention no incumbent quote available)
2. OCB WA and WA logs in IMCRM

# Email notification for failed emails

Sender: [alfred@reminder.insurancemarket.ae](mailto:alfred@reminder.insurancemarket.ae)
Recipient: Users with Renewals_manager role or users with renewals-batches-nonmotor permission

**Subject:** Renewal OCB Email Failed – Action Required

**Body of the email:**

Hi,

This is to inform you that some renewal emails scheduled for automated sending today could not be delivered due to sending failures.

**Summary:**

- **Date of attempt:** \[Insert Date\]
- **Number of total leads:** \[Insert Count\]
- **Number of total emails sent:** \[Insert Count\]
- **Number of total emails failed:** \[Insert Count\]
- **Reason(s) for failure:** \[Insert failure reasons\]

**Next Steps:**

- Review the reasons for the email failures.
- Correct any issues in the lead data.
- Re-initiate the email sending process as required.

If you need assistance or encounter any issues during the re-send, please reach out to the DT team.

# FRD: Bike Renewals CQF and OCB (Leesa)

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 25/06/2025 | 1.0         | Leesa           |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/85zu2yxtt](https://app.clickup.com/t/85zu2yxtt)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | Leesa Jeetwani                                                                       |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

![](https://t2197982.p.clickup-attachments.com/t2197982/091b5b1b-f8ef-4277-9931-c460ac401f8c/image.png)

## **A. Use-case or User story: IMCRM Updates:**

As IM, I want to create bike leads for renewal so that the customers are sent quotes timely before their policy expiry.

## **A. Requirements:**

1. The same CQF flow needs to be implemented for Bike similar to that of Motor Renewals.
2. Create leads from upload and create sheet similar to that of Motor.
   ![](https://t2197982.p.clickup-attachments.com/t2197982/a670a0c2-1fda-4b1e-82b1-65dc78525158/image.png)
   ![](https://t2197982.p.clickup-attachments.com/t2197982/a22ed9a6-aa30-420b-b98d-e7fd6dbaa759/image.png)

## **B. User story: Upload and Update of Renewal Leads**

As Renewals manager, I want to be able to update the renewal leads with additional information so that leads are modified with renewal details.

## **Requirements:**

1. Attachment below shows the data fields that are to be entered in the upload & update template and displayed in IMCRM
2. We will use existing template for motor and based on Lob selected sheets will be visible
3. The downloadable template will now be generated based on the selected Line of business (LOB) filter.
4. Introduce a field Lob ( Line of business for selecting Bike or Car)

![](https://t2197982.p.clickup-attachments.com/t2197982/19c255d1-c337-49de-be5e-d38ee41d4bdf/image.png)
![](https://t2197982.p.clickup-attachments.com/t2197982/d869772c-03ea-4e23-8a8f-0dd275ef8409/image.png)
3\. Below is the sample xlsx file that should be available under the "Download Sample XLSX" button. This is when 'Bike' is selected as the LOB.
[https://docs.google.com/spreadsheets/d/1F15auflOimWwjFfD3fCBLqIaqUIF9TO3fKKArUrZbG8/edit?gid=0#gid=0](https://docs.google.com/spreadsheets/d/1F15auflOimWwjFfD3fCBLqIaqUIF9TO3fKKArUrZbG8/edit?gid=0#gid=0)

[

docs.google.com

https://docs.google.com/spreadsheets/d/1F15auflOimWwjFfD3fCBLqIaqUIF9TO3fKKArUrZbG8/edit?gid=0#gid=0

](https://docs.google.com/spreadsheets/d/1F15auflOimWwjFfD3fCBLqIaqUIF9TO3fKKArUrZbG8/edit?gid=0#gid=0)

- Separate Excel files will be provided for each Line of Business (LOB), with specific fields tailored to the required updates. These files can be downloaded for use in the upload and update process.
- Renewal leads will be uploaded by the Renewal Manager using the Upload and Update feature.
- A standardized template will be available, mapping the upload/update fields to their corresponding fields in IMCRM

| **Upload and update Fields**                                      | **IMCRM Fields**                                              | **Additional Comments** | **Required** | **Notes**                                     | **Max Size** |
| ----------------------------------------------------------------- | ------------------------------------------------------------- | ----------------------- | ------------ | --------------------------------------------- | ------------ |
| Customer name                                                     | First name<br>Last name<br>                                   | (Customer profile )     | No           |                                               |              |
| Customer email                                                    | Email                                                         | (Customer profile )     | No           |                                               |              |
| **Customer Number**                                               | Mobile number                                                 | (Customer profile )     | No           |                                               |              |
| **Insurance Type**                                                | BIK                                                           |                         | Yes          |                                               |              |
| **Insurance Provider**                                            | Provider name (Ecom details)                                  | Ecom details            | Yes          |                                               |              |
| Product type                                                      |                                                               |                         | Yes          |                                               |              |
| **Advisor Email**                                                 |                                                               |                         | Yes          |                                               |              |
| **Policy Number**                                                 | Ask april                                                     |                         | Yes          |                                               |              |
| **Policy End date**                                               | Ask april                                                     |                         | Yes          |                                               |              |
| Batch                                                             |                                                               |                         | No           |                                               |              |
| **Bike Name**                                                     | Bike name                                                     | Quote details           | Yes          |                                               |              |
| **Bike Model**                                                    | Bike model                                                    | Quote details           | Yes          |                                               |              |
| **Bike model year**                                               | Bike model year                                               | Quote details           | Yes          |                                               |              |
| Date of birth                                                     | Date of birth                                                 | Customer profile        | Yes          |                                               |              |
| Driving experience                                                | Home Country Driving License Held For                         | Quote details           | Yes          |                                               |              |
| Nationality                                                       | Nationality                                                   | Customer profile        | Yes          |                                               |              |
| Provider name                                                     | Provider name                                                 | Ecom details            | Conditional  | Only required if Renewal premium is available |              |
| Plan name                                                         | Plan name                                                     | Ecom details            | Conditional  | Only required if Renewal premium is available |              |
| **Repair type**                                                   | Emirate of registeration                                      | Quote details           | Conditional  | Only required if Renewal premium is available |              |
| **Claim History**                                                 | Claims history                                                | Quote details           | Yes          |                                               |              |
| **Can You Provide No-Claims Letter From Your Previous Insurers?** | Can You Provide No-Claims Letter From Your Previous Insurers? | Quote details           | Yes          |                                               |              |
| **Insurer Quote No.**                                             | CHASSIS NUMBER                                                | Quote details           | Conditional  | Only required if Renewal premium is available |              |
| Bike value                                                        | Bike value                                                    | Quote details           | Yes          |                                               |              |
| **Renewal Premium**                                               | Price                                                         | Ecom details            | Optional     |                                               |              |
| **Excess**                                                        | Ask april                                                     |                         | Conditional  | Only required if Renewal premium is available |              |
| Ancillary Excess                                                  | Ask april                                                     |                         | Optional     |                                               |              |
| PAB Driver                                                        | Ask april                                                     |                         | Conditional  | Only required if Renewal premium is available |              |
| Amount - PAB Driver                                               |                                                               |                         | Conditional  | Only required if PAB driver is available      |              |
| PAB Passenger                                                     |                                                               |                         | Optional     |                                               |              |
| Amount - PAB Passenger                                            |                                                               |                         | Conditional  | Only required if PAB passenger is available   |              |
| Road Side Assistance                                              |                                                               |                         |              |                                               |              |
| Amount - Road Side Assistenace                                    |                                                               |                         |              |                                               |              |
| First Year of Registration                                        |                                                               |                         | Yes          |                                               |              |
| Trim                                                              |                                                               |                         | No           |                                               |              |
| Registration Location                                             |                                                               |                         | Yes          |                                               |              |
| Previous Advisor Email                                            |                                                               |                         | No           |                                               |              |
| Notes                                                             |                                                               |                         | No           |                                               |              |
| Is GCC                                                            |                                                               |                         | Yes          |                                               |              |

## **C. User story: Fetching of plans**

As renewal manager, I want to fetch available plans so that alternate plans can be shared with customers if required.

## **Requirements:**

1. After the Upload and Update process is completed, alternate plans should be fetched manually
   **Note:** If any entries fail during the upload and update process, the CQF team will correct them and reupload as new entries with new IDs.
   ![](https://t2197982.p.clickup-attachments.com/t2197982/0f853bc0-7208-4b1e-a0a7-39e5662df723/image.png)
   2\. After the leads are uploaded and they are in good field then they should refelect here : ![](https://t2197982.p.clickup-attachments.com/t2197982/3acaa55e-9b8b-48db-8344-b6dee8f9159e/image.png)
   3\. Bad leads should be uploaded here with the validations so they can be uploaded again by the CQF team
   4\. Validations should be based on this :[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-167078?block=block-dfba83a9-3171-4d10-bb24-24f4808f89e4)
   ![](https://t2197982.p.clickup-attachments.com/t2197982/f994cdba-6400-41b0-baaa-655bc2d12161/image.png)

### **D.User Story:** Batch-Wise Plan Fetching for Bike Renewals by CQF Team

As a CQF team member, I want to fetch plans for Bike renewal leads batch-wise from the Batches section so that I can initiate quote generation in a controlled and permission-based way.

### **Requirements:**

- After leads are uploaded, renewal manager should navigate to the Batches section.
- They can view the plans in Plan processes section
- **Incllude filed LOB here bcz they have same batch number**
  ![](https://t2197982.p.clickup-attachments.com/t2197982/986d3871-4395-419c-ae7c-2f9f3a430d33/image.png)![](https://t2197982.p.clickup-attachments.com/t2197982/92d4b1fc-f28c-4352-a938-bcd64048ff53/image.png)
- Users can search for the relevant Renewal Batch using filters (e.g., Renewal batch name)
- ![](https://t2197982.p.clickup-attachments.com/t2197982/fc2ef5d9-01ad-4c64-b29b-d3a9344e4e9b/image.png)
- The Fetch Plan button is already available; users need to select it for the respective batch to fetch plans.
- This **Fetch Plan** feature must be restricted to users with the appropriate **permission/role**.
- All existing functionalities of the manual plan-fetching process (as used in motor) should apply to **Bike renewals** as well.
- The system should log and track the fetch action for audit purposes.

### **E. User Story : Triggering OCB Emails for Bike Renewals by CQF Team**

As a CQF team member, I want to trigger OCB (One Click Buy) emails for Bike renewal leads after fetching is completed so that customers receive their renewal quotes in time.

### **Requirements:**

- After plan fetching is completed, the renewal manager needs to go back to the Batches section.
- They must search and select the relevant renewal batch for which they want to send OCB.
- The Send Emails button (already available) should be used to trigger OCB; for Bike, this button should have updated OCB
- Once OCB emails are sent, the renewal manager should be able to verify the status in the Email Batch Details section.
  put validation- if payment status is paid or partiallly paid then ocb should not go
  ![](https://t2197982.p.clickup-attachments.com/t2197982/0b524ed0-7450-4277-abbd-83e483b86247/image.png)
- This functionality should follow the same controls and permissions as other LOBs.
- It applies to all leads with the lead source as **renewal_upload**.
- IMCRM should also have the Renewal Flag in the available Plans.

![](https://t2197982.p.clickup-attachments.com/t2197982/4cf08d72-f305-4c1b-baa0-aeca25fcc909/image.png)

- Once the OCB email is sent the lead status should be updated to Quoted.

# FRD: CQF Automation: Motor Renewals

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 14.02.2025 | 1.0         | April           |              |
| 17.02.2025 | 1.1         | April           | <br>         |
| 25.02.2025 | 1.2         | April           | <br>         |
| 26.02.2025 | 1.3         | April           | <br>         |
| 05.03.2025 | 1.4         | April           | <br>         |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86ep451md](https://app.clickup.com/t/86ep451md)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestor(s)** |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **BA**                    | April                                                                                |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

## **Background:**

Currently, the Renewals module consists of manual processes, including _Upload and Create_, _Upload and Update_, _Fetch Plans_, and _Send Emails_, as we rely on data exported from Insly.

With policies now being booked directly in IMCRM, our aim is to automate the entire renewals process. All the required information is already stored within the lead, enabling us to create renewal leads and generate quotes seamlessly, enhancing the renewal journey for our clients.

## **A. Use-case or User story: Automated renewal leads creation**

As IM, I want renewal leads to be created automatically in IMCRM based on the booked policies in the system, replacing the current _Upload and Create_ and _Upload and Update_ processes.

## **A. Requirements: Automated renewal leads creation**

1. Check the leads with the payment status 'Paid' and 'Partially paid'.
   1. Ignore if the lead status is set to 'Policy cancelled' or 'Policy cancelled and reissued', or 'Cancellation pending'
   2. Check the 'Expiry date' field in the policy details section.
   3. Have to ensure this is done Private ([https://app.clickup.com/t/86ernk0uk](https://app.clickup.com/t/86ernk0uk))
2. If the lead source is 'Insly'
   1. Then check if with send update type 'Endorsement financial' and subtype 'Policy period extension'.
   2. Send update status should be 'Update booked'.
   3. Check the 'Expiry date' field in the policy details section.
   4. Have to ensure this is done Private ([https://app.clickup.com/t/86ernk0uk](https://app.clickup.com/t/86ernk0uk))
3. After the leads are identified from points 1 and 2,
   1. Check the Embedded Products section:
      1. Any EP with the payment status 'Captured' should be pre-selected when creating the renewal lead.
         1. EP should be pre-selected in the checkout page.
         2. No changes in the existing EP conditions that are always pre-selected.
         3. This process should replace the previous FRD Private ([https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098](https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098))
      2. ![](https://t2197982.p.clickup-attachments.com/t2197982/7a6b894a-6d45-4187-ad12-f177dc2f01af/image.png)
4. Create the renewal leads 90 days prior to the policy expiry.
   1. Same as the current behavior:
      1. Lead source should be renewal_upload.
      2. Lead status to be set to 'New lead'.
      3. These leads should not be included in the ILA and Buy leads.
      4. No intro or OCB email should be sent at this point.
5. Link the renewal_upload lead to the previous lead booked.
   1. Under the Last year's policy details, add another field called Previous Lead Ref-ID.
   2. Add the previous Ref-ID as a hyperlink and ensure that clicking the Ref-ID opens the old lead in a new tab to ensure that the user will be able to access the previous lead to see details and documents from the previous year.
   3. Remove the 'View legacy policy' button.[](https://app.clickup.com/2197982/docs/232ey-12705/232ey-30047?block=block-eb9cbee6-0324-4f72-b5b8-4a20c16d0f8c)
   4. ![](https://t2197982.p.clickup-attachments.com/t2197982/90e2450f-6e4a-4e22-8305-4a3e3e96b552/image.png)
6. Refer to the [table](https://app.clickup.com/2197982/docs/232ey-46718/232ey-230838?block=block-d1664167-48f2-4869-92d9-0340283fccf2) below for the required information and mapping.
7. Under _Uploaded Leads_ in the Renewals module, the Renewals Manager should continue to see how many leads are created and its status. 1. In case of bad data, the Renewals Manager should be able to export and reupload the file using the existing upload and create functionality as per the existing behavior.
   ![](https://t2197982.p.clickup-attachments.com/t2197982/b7babe28-7390-4be0-a56a-5711bf184ffb/image.png)
8. Under the _Search_ in the Renewals module, the Renewals Manager should be able to search for all **renewal_upload** leads created within a **selected policy expiry date range** and export the search results if needed to ensure that the leads created are complete.
   FRD: IMCRM - Search Filter Enhancement ([https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4](https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4))

[Renewal-2025-02-21.csv](https://t2197982.p.clickup-attachments.com/t2197982/d73fbc0b-06a8-4edd-9dce-8b26118f5481/Renewal-2025-02-21.csv)

1. If in case Private ([https://app.clickup.com/t/86epge9q8](https://app.clickup.com/t/86epge9q8)) is not ready, from point 8, the Renewals Manager can export the renewal leads created and should be able to assign the leads to the advisor and batch using upload and update.

| **Upload and create/update** | **IMCRM sections**                                                                   | **Policy booked lead**      | **IMCRM sections**                 | **Renewal_upload mapping**  |
| ---------------------------- | ------------------------------------------------------------------------------------ | --------------------------- | ---------------------------------- | --------------------------- |
| Customer name                | <br><br><br><br>Customer profile                                                     | Insured first and last name | <br><br><br><br>Customer profile   | Insured first and last name |
| Customer e-mail              | Email                                                                                | Email                       |
| Customer mobile number       | Mobile                                                                               | Mobile                      |
| Date of birth                | Date of birth                                                                        | Date of birth               |
| Driving experience           | UAE license held for                                                                 | UAE license held for (+1)   |
| Nationality                  | Nationality                                                                          | Nationality                 |
| Insurer provider             | E-com details                                                                        | Provider name               | Car details                        | Currently insured with      |
| Product type                 | Based on the plan name                                                               | Assumptions                 | Current insurance                  |
| Car make                     | <br><br><br><br>Car details                                                          | Car make                    | <br><br><br>Car details            | Car make                    |
| Car model                    | Car model                                                                            | Car model                   |
| Model year                   | Car model year                                                                       | Car model year              |
| Registration location        | Emirate of registration                                                              | Emirate of registration     |
| Trim (optional)              | Trim                                                                                 | Trim                        |
| Previous advisor email       | Advisor                                                                              | Last year's policy details  | Previous Advisor                   |
| First year of registration   | <br>Assumptions                                                                      | Year of first registration  | <br>Assumptions                    | Year of first registration  |
| Is GCC                       | Is GCC standard                                                                      | Is GCC standard             |
| Policy number                | <br><br>Policy details                                                               | Policy number               | <br><br>Last year's policy details | Previous policy number      |
| Policy start date            | Start date                                                                           | Previous policy start date  |
| Policy end date              | Expiry date                                                                          | Previous policy expiry date |
| Gross premium                | Price (VAT applicable)                                                               | Previous policy premium     |
| Advisor email                | Private ([https://app.clickup.com/t/86epge9q8](https://app.clickup.com/t/86epge9q8)) |                             |                                    |                             |
| Batch                        | Private ([https://app.clickup.com/t/86epge9q8](https://app.clickup.com/t/86epge9q8)) |                             |                                    |                             |

| **Renewal plan**     |
| -------------------- | ----------------------------------------------------------------------------------------------- |
| Provider name        | <br><br><br><br><br><br><br>Will be from Renewals API based on the currently insured with field |
| Plan name            |
| Repair type          |
| Claims history       |
| NC letter            |
| Insurer quote number |
| Car value            |
| Renewal premium      |
| Excess               |
| Ancillary excess     |
| Add-ons              |

## **B. Use-case or User story: Fetch plans**

As IM, I want the _Fetch Plans_ process to be automated to minimize the dependency on manual triggering.

## **B. Requirements: Fetch plans**

1. Once the renewal leads are created, generate quotes from the internal calculators, APIs, and any existing PUA logic in place.
   1. Trigger: Lead status 'New lead' and 60 days policy expiry from the current date.
   2. System should try fetching the renewal quote for 7 days if not initially available.
2. The renewal plan should have the 'Renewal' flag (same as current behavior).
   1. Renewals API to be called based on the 'Currently insured with' field.
   2. In case the incumbent insurer does not support renewals API, then the existing upload and update should continue to work
      1. Minimize the required fields [upload and update template](https://docs.google.com/spreadsheets/d/11ihpMUL7NtpS27OC0g8FvFGbzjvqHWItuIEePWlXhrw/edit?gid=0#gid=0).
      2. Same validation will be applied against the previous policy number and previous policy expiry date in order to determine which renewal_upload lead needs to be updated.
3. Once all quotes are generated, update the lead status from 'New lead' to 'Quoted'.
4. Under _Batches > Fetch Plans_ in the Renewals module, the Renewals Manager should continue to see the plans process details and its status. 1. In case of failed fetch plans, the Renewals Manager should be able to trigger it manually as per the existing behavior.
   ![](https://t2197982.p.clickup-attachments.com/t2197982/ada642ed-76a1-480c-8f6b-0af1b4d56d22/image.png)

## **C. Use-case or User story: Send OCB renewal emails**

As IM, I want the _Send Emails_ process to be automated to minimize the dependency on manual triggering.

## **C. Requirements: Send OCB renewal emails**

1. Once all the quotes are generated, trigger the OCB renewal email 53 days prior to the previous policy expiry date.
   1. If API call fails, this should not stop the system to trigger the OCB renewal email which could be 0 quote scenario.
   2. Lead status remains as 'New lead'.
2. Update the lead status from 'Quoted' or 'New lead' to 'OCB email sent' (new lead status to be created).
   1. Trigger for the automated follow-up emails needs to be updated.
3. Under _Batches > Send Emails_ in the Renewals module, the Renewals Manager should continue to see the number of emails sent and its status. 1. In case of failed send emails, the Renewals Manager should be able to trigger it manually as per the existing behavior.
   ![](https://t2197982.p.clickup-attachments.com/t2197982/d69746a0-491b-4d19-92df-4d05290556d0/image.png)

## **D. Use-case or User story: Bike renewals under Car quotes**

As IM, I want bike renewal leads to be created under bike quotes instead of car quotes, ensuring that clients see accurate details and available plans for renewal.

## **D. Requirements: Bike renewals under Car quotes**

1. After identifying the leads from points 1 and 2[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-230838?block=block-e2a76fca-98d9-4277-bdcc-1fb2b4af256a) 1. Check the vehicle body type mentioned in the assumptions section. 2. If the vehicle body type is 'Bike', then the renewal lead has to be created under bike quotes.
   ![](https://t2197982.p.clickup-attachments.com/t2197982/e2fba155-2a62-4765-9361-5163a9812d25/image.png)

[Flow](https://miro.com/app/board/o9J_lUzX-RM=/?moveToWidget=3458764620970940202&cot=14)

![](https://t2197982.p.clickup-attachments.com/t2197982/6bac12a3-dcb6-48dd-a12d-4046152f6fff/image.png)

## **Open Questions:**

| **No.** | **Date Raised**                   | **Question/Concern**                   | **Raised by**                                      | **Status** | **Outcome**                                                                                                                                    |
| ------- | --------------------------------- | -------------------------------------- | -------------------------------------------------- | ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| 1       | 14 Feb                            | Do we automate the advisor assignment? | AP                                                 | Closed<br> | Advisor and SIC flow will be taken care of by this<br><br>Private ([https://app.clickup.com/t/86epge9q8](https://app.clickup.com/t/86epge9q8)) |
| 2       | What happens to the SIC 3.0 flow? |
| 3       | Do we automate the motor batches? | Open                                   | Have to check the data with and without balancing. |
|         |                                   |                                        |                                                    |            |                                                                                                                                                |

## **Additional resources:**

1. Private ([https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098](https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098))
2. Private ([https://app.clickup.com/2197982/docs/232ey-48978/232ey-155878](https://app.clickup.com/2197982/docs/232ey-48978/232ey-155878))
3. Private ([https://app.clickup.com/t/86epwqqq8](https://app.clickup.com/t/86epwqqq8))
4. FRD: IMCRM - Search Filter Enhancement ([https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4](https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4))

## **Use-case References:**

1. Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-184518](https://app.clickup.com/2197982/docs/232ey-46718/232ey-184518))

# FRD: CQF Automation: Motor Renewals - Phase 1

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 14.02.2025 | 1.0         | April           |              |
| 17.02.2025 | 1.1         | April           | <br>         |
| 25.02.2025 | 1.2         | April           | <br>         |
| 26.02.2025 | 1.3         | April           | <br>         |
| 05.03.2025 | 1.4         | April           | <br>         |
| 17.06.2025 | 1.5         | April           | <br>         |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86ep451md](https://app.clickup.com/t/86ep451md)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestor(s)** |                                                                                      |     |
| **Approvers**             |                                                                                      |     |
| **BA**                    | April                                                                                |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

## **A. Use-case or User story: Automated renewal leads creation**

As IM, I want renewal leads to be created automatically in IMCRM based on the booked policies in the system, replacing the current _Upload and Create_ processes.

## **A. Requirements: Automated renewal leads creation**

1. Check the leads with the payment status 'Paid' and 'Partially paid'.
   1. Ignore if the lead status is 'Policy cancelled' or 'Policy cancelled and reissued', or 'Cancellation pending'
      1. Ensure to check the child lead for 'Policy cancelled and reissued' cases
   2. Check the 'Expiry date' field in the Policy Details section.
      1. Should be 120 days from the current date
2. If the lead source is 'Insly'
   1. Then check if with send update type 'Endorsement financial' and subtype 'Policy period extension'.
   2. Send update status should be 'Update booked'.
   3. Check the 'Expiry date' field in the Policy Details section.
      1. Should be 120 days from the current date
3. After the leads are identified from points 1 and 2,
   1. Check the Embedded Products section:
      1. Any EP with the payment status 'Captured' should be pre-selected when creating the renewal lead.
         1. System should respect the pre-requisites at all times for EP. Any existing EP policy logic should continue to be in place. Example, if chassis number is required to issue the EP, then the system should continue requiring it.
         2. Captured EP should be pre-selected in the checkout page.
            1. No changes in the existing EP conditions that are always pre-selected.
         3. This process should replace the previous FRD Private ([https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098](https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098))
4. Create the renewal leads 120 days prior to the policy expiry.
   1. Same as the current behavior:
      1. Lead source should be renewal_upload.
      2. Lead status to be set to 'New lead'.
      3. These leads should not be included in the ILA and Buy leads.
      4. No intro or OCB email should be sent at this point.
   2. Same validation must continue to apply:
      1. The system will not create a renewal_upload lead if a lead already exists with the same **policy number** and **policy expiry date**. This validation ensures duplicate renewal leads are not created.
5. Audit logs section should reflect the lead creation.
6. Link the renewal_upload lead to the previous lead booked.
   1. Under the Last year's policy details, add another field called Previous Lead Ref-ID.
   2. Add the previous Ref-ID as a hyperlink and ensure that clicking the Ref-ID opens the old lead in a new tab so that the user will be able to access the previous lead to see details and documents from the previous year.
   3. Remove the 'View legacy policy' button in the Last Year's Policy Details section
7. Refer to the [table below](https://app.clickup.com/2197982/docs/232ey-46718/232ey-269038?block=block-83fef04b-f65f-4932-a350-aaf978f496ee) for the required information and mapping.
8. Under _Uploaded Leads_ in the Renewals module, the Renewals Manager should continue to see how many leads are created and its status.
   1. In case of bad data:
      1. Send an [email notification](https://app.clickup.com/2197982/docs/232ey-46718/232ey-269158) to the Renewals Manager regarding the failed leads.
         1. This email notification will be sent to the users with Renewals_Manager role in IMCRM
      2. The Renewals Manager should be able to export and reupload the file using the existing upload and create functionality as per the existing behavior.
9. Under the _Search_ in the Renewals module, the Renewals Manager should be able to search for all **renewal_upload** leads created within a **selected policy expiry date range** and export the search results so the Renewals Manager can work on the advisor allocation and balancing.
   FRD: IMCRM - Search Filter Enhancement ([https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4](https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4))

[Renewal-2025-02-21.csv](https://t2197982.p.clickup-attachments.com/t2197982/d73fbc0b-06a8-4edd-9dce-8b26118f5481/Renewal-2025-02-21.csv)

| **IMCRM Sections**                    | **Fields**                                                                             | **Notes**                                                               | **Required** |
| ------------------------------------- | -------------------------------------------------------------------------------------- | ----------------------------------------------------------------------- | ------------ |
| Car details                           | Registration type                                                                      | Personal by default but can be updated to Company via upload and update | No           |
| Customer type                         | Based on the Registration type                                                         | No                                                                      |
| Customer age                          | Based on the DOB                                                                       | No                                                                      |
| Lead source                           | Renewal_upload                                                                         | Yes                                                                     |
| Car make                              | Car make from the policy booked lead                                                   | Yes                                                                     |
| Car model                             | Car model from the policy booked lead                                                  | Yes                                                                     |
| Cylinder                              | Cylinder from the policy booked lead                                                   | No                                                                      |
| Chassis number                        | Chassis number from the policy booked lead if available                                | No                                                                      |
| Trim                                  | Trim from the policy booked lead                                                       | No                                                                      |
| Car model year                        | Car model year from the policy booked lead                                             | Yes                                                                     |
| Car value                             | Based on the value from the upload and update sheet                                    | No                                                                      |
| Car value (at enquiry)                | Based on the value from the upload and update sheet                                    | No                                                                      |
| Vehicle type                          | Vehicle type based on the car make and model                                           | Yes                                                                     |
| Seat capacity                         | Based on the trim selected                                                             | No                                                                      |
| Emirate of registration               | Emirate of registration from the policy booked lead                                    | Yes                                                                     |
| Type of car insurance                 | Based on the plan name from the policy booked lead (Comprehensive or Third Party Only) | No                                                                      |
| Currently insured with                | Based on the Provider Name from the policy booked lead                                 | Yes                                                                     |
| Claims history                        | Based on the value from the upload and update sheet                                    | No                                                                      |
| NC letter                             | Based on the value from the upload and update sheet                                    | No                                                                      |
| Customer profile                      | First name                                                                             | First name from the policy booked lead                                  | Yes          |
| Last name                             | Last name from the policy booked lead                                                  | Yes                                                                     |
| Insured first name                    | Insured first name from the policy booked lead                                         | Yes                                                                     |
| Insured last name                     | Insured last name from the policy booked lead                                          | Yes                                                                     |
| Mobile number                         | Mobile number from the policy booked lead                                              | Yes                                                                     |
| Email                                 | Email from the policy booked lead                                                      | Yes                                                                     |
| Date of birth                         | Date of birth from the policy booked lead                                              | No                                                                      |
| Gender                                | Gender from the policy booked lead                                                     | No                                                                      |
| Nationality                           | Nationality from the policy booked lead                                                | No                                                                      |
| UAE licence held for                  | UAE licence held for from the policy booked lead plus 1                                | No                                                                      |
| Home country driving license held for | Home country driving license held for from the policy booked lead plus 1               | No                                                                      |
| Last year's policy details            | Renewal batch number                                                                   | Based on the value from the upload and update sheet                     | No           |
| Previous policy number                | Policy number from the policy booked lead                                              | Yes                                                                     |
| Previous policy expiry date           | Expiry date from the policy booked lead                                                | Yes                                                                     |
| Previous policy premium               | Total price from the policy booked lead                                                | No                                                                      |
| Previous policy start date            | Start date from the policy booked lead                                                 | Yes                                                                     |
| Previous advisor                      | Advisor from the policy booked lead                                                    | No                                                                      |
| Embedded products                     | Courier my original documents                                                          | Always pre-selected                                                     |              |
| Driver medical cover                  | Pre-selected if selected from the policy booked lead                                   |                                                                         |

## **B. Use-case or User story: Upload and update template**

As IM, I want the _upload and update_ to be simplified so that the CQF team doesn't have to pull again the information that is already in the renewal lead created by the system.

## **B. Requirements: Upload and update template**

1. Update the existing upload and update template to [this](https://docs.google.com/spreadsheets/d/16hKkR-FuBPXS9EQCXaX0hrZPGIa94IxLDeotSR0fwU4/edit?gid=0#gid=0).

| **Fields**                        | **Required**               | **Notes**                                         |
| --------------------------------- | -------------------------- | ------------------------------------------------- |
| **Customer Name**                 | No                         |                                                   |
| **Customer Email**                | No                         |                                                   |
| **Customer Mobile**               | No                         |                                                   |
| **Product Type**                  | Yes                        |                                                   |
| **Advisor Email**                 | Conditional (SIC - Yes/No) | Required if SIC - No<br>Not required if SIC - Yes |
| **Policy Number**                 | Yes                        |                                                   |
| **Policy End Date**               | Yes                        |                                                   |
| **Batch**                         | No                         |                                                   |
| **Registration Type**             | Yes                        |                                                   |
| **Vehicle Use**                   | Conditional                | Required if Registration Type is Company          |
| **Business Activity**             | Conditional                | Required if Vehicle Use is Commercial             |
| **Date of Birth**                 | Conditional                | Required if Vehicle Use is Private                |
| **Driving Experience**            | Conditional                | Required if Vehicle Use is Private                |
| **Nationality**                   | Conditional                | Required if Vehicle Use is Private                |
| **Provider Name**                 | Conditional                | Required if Renewal Premium is available          |
| **Plan Name**                     | Conditional                | Required if Provider Name is available            |
| **Repair Type**                   | Conditional                | Required if Provider Name is available            |
| **Claims History**                | Yes                        |                                                   |
| **NC Letter**                     | Yes                        |                                                   |
| **Insurer Quote Number**          | Conditional                | Required if Renewal Premium is available          |
| **Car Value (From Insurer)**      | Yes                        |                                                   |
| **Renewal Premium**               | No                         |                                                   |
| **Excess**                        | Conditional                | Required if Renewal Premium is available          |
| **Ancillary Excess**              | No                         |                                                   |
| **PAB Driver**                    | Conditional                | Required if Renewal Premium is available          |
| **Amount - PAB Driver**           | Conditional                | Required if Renewal Premium is available          |
| **PAB Passenger**                 | Conditional                | Required if Renewal Premium is available          |
| **Amount - PAB Passenger**        | Conditional                | Required if Renewal Premium is available          |
| **Rent a car**                    | Conditional                | Required if Renewal Premium is available          |
| **Amount - Rent a Car**           | Conditional                | Required if Renewal Premium is available          |
| **Oman Cover**                    | Conditional                | Required if Renewal Premium is available          |
| **Amount - Oman Cover**           | Conditional                | Required if Renewal Premium is available          |
| **Road Side Assistance**          | Conditional                | Required if Renewal Premium is available          |
| **Amount - Road Side Assistance** | Conditional                | Required if Renewal Premium is available          |
| **Notes**                         | Optional                   |                                                   |

1. The provider mentioned in the 'Currently insured with' field in the Car Details section should be the same as the value entered in the Provider Name column, otherwise throw a validation error.
2. Existing validations should continue to apply.
3. No changes in the fetch plans and send emails processes.

**For internal reference:**
With premium: 21 - 26 fields depending on the registration type selected
Without premium: 5 - 10 fields depending on the registration type selected

[Flow](https://miro.com/app/board/o9J_lUzX-RM=/?moveToWidget=3458764632140620540&cot=14)

![](https://t2197982.p.clickup-attachments.com/t2197982/b637daf4-5add-42c9-9b22-6e64c6719a76/image.png)

## **Additional resources:**

1. Private ([https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098](https://app.clickup.com/2197982/docs/232ey-73478/232ey-229098))
2. Private ([https://app.clickup.com/2197982/docs/232ey-48978/232ey-155878](https://app.clickup.com/2197982/docs/232ey-48978/232ey-155878)) - internal process
3. Private ([https://app.clickup.com/t/86epwqqq8](https://app.clickup.com/t/86epwqqq8))
4. FRD: IMCRM - Search Filter Enhancement ([https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4](https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4))

## **Use-case References:**

1. Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-184518](https://app.clickup.com/2197982/docs/232ey-46718/232ey-184518))

Check chassis number and update trim
Prepare BRD

# Email notification for failed leads

**Subject:** Renewal Lead Creation Failed – Action Required

**Body:**
Hi,

This is to inform you that some renewal leads scheduled for automated creation today could not be processed due to data issues.

**Summary:**

- **Date of attempt:** \[Insert Date\]
- **Number of failed leads:** \[Insert Count\]
- **Reason(s):** \[Insert validation error messages\]

Sample table that can be sent as the body of the email:
![](https://t2197982.p.clickup-attachments.com/t2197982/b649ef4a-17b9-4d69-96ea-b1f15d5153d4/image.png)

**Next Steps:**

- Please download the attached file with the list of failed records.
- Review and correct the data where needed.
- Re-upload the corrected file using the _Upload and Create_ functionality in IMCRM.

If you need assistance or encounter any issues during re-upload, feel free to reach out to the DT team.

# CQF Automation: Non-motor Renewals Phase 1 (except Health)

## **Meta Details:**

| **FRD Name**         | CQF Automation: Non-motor Renewals Phase 1 (except Health) | **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86eu2czp9](https://app.clickup.com/t/86eu2czp9)) |
| -------------------- | ---------------------------------------------------------- | ------------------------- | ------------------------------------------------------------------------------------ |
| **Prepared by**      | April Pascual                                              | **Designation**           | DT Manager                                                                           |
| **Reviewed by**      | Mohammad Asad Alam                                         | **Designation**           | Head of Product and CX                                                               |
| **Reviewed date**    | 26.12.2025                                                 | **Status**                | Approved                                                                             |
| **Business Review**  |                                                            | **Designation**           |                                                                                      |
| **Reviewed Date**    |                                                            | **Status**                |                                                                                      |
| **Level 1 Approver** | Hitesh Motwani                                             | **Designation**           | Deputy CEO                                                                           |
| **L1 Approval Date** | 20.01.2026                                                 | **Status**                | Approved                                                                             |
| **Level 2 Approver** | Avinash Babur                                              | **Designation**           | CEO                                                                                  |
| **L2 Approval Date** | 21.01.2026                                                 | **Status**                | Approved                                                                             |
| **Version Control**  | 1.0                                                        | **Status**                | Approved                                                                             |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86eu2czp9](https://app.clickup.com/t/86eu2czp9)) |
| ------------------------- | ------------------------------------------------------------------------------------ |
| **Approvers**             |                                                                                      |
| **BA**                    | April Pascual                                                                        |
| **Version Control**       | \[Maintained in CU Document History\]                                                |

Please delete sections that you don't use

## **Background:**

Non-motor renewals (excluding Health) currently rely on a mix of manual uploads and fragmented processes, resulting in higher operational effort. This initiative introduces **automation for renewal lead creation** and **enhancements to Upload & Create** to align non-motor renewals with established Motor workflows.

This is an **enhancement** to existing renewal processes, enabling automated creation of renewal leads from booked policies, improving LOB accuracy (e.g., Bike vs Car, Group Medical, Yacht), and standardising validations and mappings. The scope explicitly excludes Health.

## **Business Value Mapping**

\[**Every user story** must tie to a measurable value; e.g. “Reduces advisor manual work by X hrs/month”, “Expected to increase NPS by 10%”\]

| **KPI/Metric**                    | **Target/Description**                                                | **Type**     |
| --------------------------------- | --------------------------------------------------------------------- | ------------ |
| Renewal lead accuracy (LOB)       | Achieve 100% correct LOB allocation (Bike, Yacht)                     | Quality      |
| Automated renewal lead creation   | 100% of eligible booked policies generate renewal leads automatically | Automation   |
| Validation consistency            | 100% alignment with Motor validation rules where applicable           | Data Quality |
| Manual renewal lead creation      | 0% manual creation for supported non-motor LOBs                       | Efficiency   |
| Regression impact to Motor/Travel | 0 functional regressions post-release                                 | Stability    |

## **MoSCoW Prioritization Table**

| **Requirement**                                                                   | **Must/Should/Could/Won’t** | **Rationale (one line)**                 |
| --------------------------------------------------------------------------------- | --------------------------- | ---------------------------------------- |
| Automated renewal lead creation from booked policies                              | Must                        | Core objective replacing Upload & Create |
| Upload & Create enhancements aligned with Motor checks                            | Must                        | Ensures data consistency                 |
| Correct LOB allocation (Bike vs Car, Yacht vs Marine Hull (Yacht, Boat or Vessel) | Must                        | Enables batch communication              |
| Include payment status **CREDIT_APPROVED**                                        | Must                        | Required for accurate eligibility        |
| Enable Upload & Create for Yacht quotes                                           | Must                        | Completes non-motor coverage             |
| Add Previous Commission & Previous Ref-ID fields                                  | Should                      | Improves data uniformity                 |
| Advisor assignment automation                                                     | Won't                       | Out of scope for this phase              |

## **A. Use-case or User story: Automated renewal leads creation**

**As a** Renewals Manager,
**I want to** automatically create renewal leads under the correct line of business based on vehicle type, insurance type, and payment status,
**so that** renewal leads are classified accurately, processed under the right workflows, and handled consistently across non-motor lines.

## **A. Requirements: Automated renewal leads creation**

1. Follow the same checks (from points 1 to 9) used for motor[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-269038?block=block-e2a76fca-98d9-4277-bdcc-1fb2b4af256a)
   1. Include the payment status CREDIT_APPROVED
2. Point 3 for EP, only whenever applicable for the line of business[](https://app.clickup.com/2197982/docs/232ey-46718/232ey-269038?block=block-bb048f18-ecc0-4bf1-8b92-a8402aa19150)
3. Point 7 for the mapping, please refer to the links below:
   1. [Home](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284638)
   2. [Pet](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284658)
   3. [Bike](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284678)
   4. [Cycle](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284698)
   5. [Yacht](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284718)
   6. [Group Medical](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284738)
   7. [Corpline](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284758)
4. If the lead is from car quotes but the 'Vehicle type' in the Car details section or 'Vehicle body type' in Assumptions section is 'Bike', then the renewal lead should be created under Bike quotes Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-284678](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284678))
   1. Enable upload and create for bike quotes.
5. Store the Insurer Provider and this info should reflect in UI and export from the Renewals Search submodule FRD: IMCRM - Search Filter Enhancement ([https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4](https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4))
6. If the business insurance type is 'Group medical', then the lead should always be created under Group Medical quotes.
7. Yacht quotes under personal quotes should continue being created under personal quotes.
   1. Enable upload and create for yacht quotes.
8. Creation of these leads should start running at 4:00 AM GST.
   1. The time specified above ensures that lead creation does not run concurrently with **Motor leads**, which start processing at 3:00 AM GST.
9. Implement the same CMS configuration used for Motor
   1. NONMOTOR_CQF_RENEWALS_SWITCH
      1. Allowing this process to be enabled or disabled
   2. NONMOTOR_CQF_RENEWALS_DAYS_THRESHOLD
      1. The number of days to be modified whenever required

## **B. Use-case or User story: Upload and create enhancement**

**As a** Renewals Manager,
**I want to** capture the Previous Commission and Previous Ref-ID during upload and create,
**so that** historical policy details are stored accurately, searchable in renewal exports, and easily accessible via clickable references in IMCRM.

## **B. Requirements: Upload and create enhancement**

1. Add two more columns in the upload and create template:
   1. Previous Commission
      1. This needs to be stored and mapped to the Renewals Search export file
   2. Previous Ref-ID
      1. The input would be text (Ref-ID) and has to be displayed as a hyperlink in the Previous Ref-ID field under Last Year's Policy Details section in IMCRM.
2. See attached for the [new template](https://docs.google.com/spreadsheets/d/1hUBQXO0sv4-2uVHNxtDwE9zExEAwoZr7f9ebiKmsw4A/edit?gid=0#gid=0). The order of the fields has to be followed as per the attached file.

## **Impact & Gap Analysis:**

| **System/Module**           | **Impact Summary**                          | **Mitigation/Owner** |
| --------------------------- | ------------------------------------------- | -------------------- |
| Renewal Engine              | Automate lead creation from booked policies | CRM Dev              |
| Upload & Create (Non-Motor) | Apply Motor-aligned checks and mappings     | CRM Dev              |
| LOB Determination Logic     | Route Bike, Group Medical, Yacht correctly  | Backend Dev          |
| Templates & Mapping         | Add new columns; enforce field order        | CRM / BA             |
| Advisor Assignment          | Interim handling until automation           | Ops / BA             |
| QA / Regression             | Ensure no impact to Motor/Travel            | QA Lead              |

## **Assumptions:**

1. Upload and create is enabled for Bike and Yacht.
2. Booked policy data is accurate and sufficient to trigger renewal creation.
3. Motor validation logic can be reused safely for non-motor where specified.
4. Advisor assignment automation may follow later without blocking Phase 1.

## **Open Questions:**

| **No.** | **Date Raised** | **Question/Concern**                                                                                                                                                       | **Raised by** | **Status** | **Outcome**                                                                                |
| ------- | --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------- | ---------- | ------------------------------------------------------------------------------------------ |
| 1       | 15.07.2025      | What about upload and update for the LOBs not supported yet? This is how allocations will be handled. Unless, the automation of advisor assignment will go-live with this. | April         | Closed     | Simple upload and update for non other LOBs and email reminder to be triggered from IMCRM. |

## **Additional resources:**

Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-269038](https://app.clickup.com/2197982/docs/232ey-46718/232ey-269038))

## **Use-case References:**

Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-269038](https://app.clickup.com/2197982/docs/232ey-46718/232ey-269038))

# Home

| **IMCRM Sections**               | **Fields**                                                   | **Notes**                                            | **Required** |
| -------------------------------- | ------------------------------------------------------------ | ---------------------------------------------------- | ------------ |
| Home details                     | Customer type                                                | Based on AML screening                               | No           |
| Company name                     | Company name from the policy booked lead                     | No                                                   |
| Company address                  | Company address from the policy booked lead                  | No                                                   |
| Advisor                          | Based on the upload and update file                          | No                                                   |
| Quote details                    | Ownership status                                             | Ownership status from the policy booked lead         | No           |
| Type of property                 | Type of property from the policy booked lead                 | No                                                   |
| Type of owner's occupancy        | Type of owner's occupancy from the policy booked lead        | No                                                   |
| Has contents                     | Has contents from the policy booked lead                     | No                                                   |
| Contents AED                     | Contents AED from the policy booked lead                     | No                                                   |
| Has building                     | Has building from the policy booked lead                     | No                                                   |
| Building AED                     | Building AED from the policy booked lead                     | No                                                   |
| Has personal belongings          | Has personal belongings from the policy booked lead          | No                                                   |
| Personal belongings AED          | Personal belongings AED from the policy booked lead          | No                                                   |
| Claim history                    | Based on the value from the upload and update sheet          | No                                                   |
| Currently insured with           | Based on the Provider Name from the policy booked lead       | No                                                   |
| Customer profile                 | First name                                                   | First name from the policy booked lead               | Yes          |
| Last name                        | Last name from the policy booked lead                        | Yes                                                  |
| Insured first name               | Insured first name from the policy booked lead               | Yes                                                  |
| Insured last name                | Insured last name from the policy booked lead                | Yes                                                  |
| Mobile number                    | Mobile number from the policy booked lead                    | Yes                                                  |
| Email                            | Email from the policy booked lead                            | Yes                                                  |
| Nationality                      | Nationality from the policy booked lead                      | Yes                                                  |
| Date of birth                    | Date of birth from the policy booked lead                    | Yes                                                  |
| Gender                           | Gender from the policy booked lead                           | No                                                   |
| Address                          | Address from the policy booked lead                          | No                                                   |
| Location area                    | Based on the value from the upload and update sheet          | No                                                   |
| Floor and villa/apartment number | Floor and villa/apartment number from the policy booked lead | No                                                   |
| Villa/building name              | Villa/building name from the policy booked lead              | No                                                   |
| Street name                      | Street name from the policy booked lead                      | No                                                   |
| Last year's policy details       | Renewal batch number                                         | Based on the previous policy expiry date (automated) | Yes          |
| Previous policy number           | Policy number from the policy booked lead                    | Yes                                                  |
| Previous policy expiry date      | Expiry date from the policy booked lead                      | Yes                                                  |
| Previous policy premium          | Total price from the policy booked lead                      | No                                                   |
| Previous policy start date       | Start date from the policy booked lead                       | Yes                                                  |
| Previous advisor                 | Advisor from the policy booked lead                          | No                                                   |

# Pet

| **IMCRM Sections**                               | **Fields**                                     | **Notes**                                            | **Required** |
| ------------------------------------------------ | ---------------------------------------------- | ---------------------------------------------------- | ------------ |
| Pet detail                                       | Customer type                                  | Based on AML screening                               | No           |
| Advisor                                          | Based on the upload and update file            | No                                                   |
| Quote details                                    | Type of pet                                    | Type of pet from the policy booked lead              | Yes          |
| Breed of pet                                     | Breed of pet from the policy booked lead       | Yes                                                  |
| Age of pet                                       | Age of pet from the policy booked lead         | Yes                                                  |
| Is neutered                                      | Is neutered from the policy booked lead        | Yes                                                  |
| Is microchipped                                  | Is microchipped from the policy booked lead    | Yes                                                  |
| Microchip No                                     | Microchip No from the policy booked lead       | Yes                                                  |
| Is mixed breed                                   | Is mixed breed from the policy booked lead     | Yes                                                  |
| Has injury                                       | Has injury from the policy booked lead         | Yes                                                  |
| Gender                                           |  Gender from the policy booked lead            | Yes                                                  |
| Accommodation type                               | Accommodation type from the policy booked lead | No                                                   |
| Possession type                                  | Possession type from the policy booked lead    | No                                                   |
| Customer profile<br><br><br><br><br><br><br><br> | First name                                     | First name from the policy booked lead               | Yes          |
| Last name                                        | Last name from the policy booked lead          | Yes                                                  |
| Insured first name                               | Insured first name from the policy booked lead | Yes                                                  |
| Insured last name                                | Insured last name from the policy booked lead  | Yes                                                  |
| Mobile number                                    | Mobile number from the policy booked lead      | Yes                                                  |
| Email                                            | Email from the policy booked lead              | Yes                                                  |
| Nationality                                      | Nationality from the policy booked lead        | Yes                                                  |
| Date of birth                                    | Date of birth from the policy booked lead      | Yes                                                  |
| Gender                                           | Gender from the policy booked lead             | No                                                   |
| Last year's policy details                       | Renewal batch number                           | Based on the previous policy expiry date (automated) | Yes          |
| Previous policy number                           | Policy number from the policy booked lead      | Yes                                                  |
| Previous policy expiry date                      | Expiry date from the policy booked lead        | Yes                                                  |
| Previous policy premium                          | Total price from the policy booked lead        | No                                                   |
| Previous policy start date                       | Start date from the policy booked lead         | Yes                                                  |
| Previous advisor                                 | Advisor from the policy booked lead            | No                                                   |

# Bike

| **IMCRM Sections**                               | **Fields**                                                                             | **Notes**                                           | **Required** |
| ------------------------------------------------ | -------------------------------------------------------------------------------------- | --------------------------------------------------- | ------------ |
| Bike detail                                      | Customer type                                                                          | Based on AML screening                              | No           |
| Advisor                                          | Based on the upload and update file                                                    | No                                                  |
| Quote details                                    | Bike make                                                                              | Bike make from the policy booked lead               | No           |
| Bike model                                       | Bike model from the policy booked lead                                                 | No                                                  |
| CC                                               | CC from the policy booked lead                                                         | No                                                  |
| Bike model year                                  | Bike model year from the policy booked lead                                            | No                                                  |
| First registration date                          | First registration date from the policy booked lead                                    | No                                                  |
| Bike value                                       | Based on the upload and update file                                                    | No                                                  |
| Bike value (at enquiry)                          | Based on the upload and update file                                                    | No                                                  |
| Seat capacity                                    | Seat capacity from the policy booked lead                                              | No                                                  |
| Chassis number                                   | Chassis number from the policy booked lead                                             | No                                                  |
| Emirate of registration                          | Emirate of registration from the policy booked lead                                    | No                                                  |
| Type of bike insurance                           | Based on the plan name from the policy booked lead (Comprehensive or Third Party Only) | No                                                  |
| Claim history                                    | Based on the value from the upload and update sheet                                    | No                                                  |
| No-claims letter                                 | Based on the value from the upload and update sheet                                    | No                                                  |
| Customer profile<br><br><br><br><br><br><br><br> | First name                                                                             | First name from the policy booked lead              | Yes          |
| Last name                                        | Last name from the policy booked lead                                                  | Yes                                                 |
| Insured first name                               | Insured first name from the policy booked lead                                         | Yes                                                 |
| Insured last name                                | Insured last name from the policy booked lead                                          | Yes                                                 |
| Mobile number                                    | Mobile number from the policy booked lead                                              | Yes                                                 |
| Email                                            | Email from the policy booked lead                                                      | Yes                                                 |
| Nationality                                      | Nationality from the policy booked lead                                                | Yes                                                 |
| Date of birth                                    | Date of birth from the policy booked lead                                              | Yes                                                 |
| Gender                                           | Gender from the policy booked lead                                                     | No                                                  |
| UAE license held for                             | UAE license held for from the policy booked lead plus 1                                | No                                                  |
| Home country driving license held for            | Home country driving license held for from the policy booked lead plus 1               | No                                                  |
| Last year's policy details                       | Renewal batch number                                                                   | Based on the value from the upload and update sheet | No           |
| Previous policy number                           | Policy number from the policy booked lead                                              | Yes                                                 |
| Previous policy expiry date                      | Expiry date from the policy booked lead                                                | Yes                                                 |
| Previous policy premium                          | Total price from the policy booked lead                                                | No                                                  |
| Previous policy start date                       | Start date from the policy booked lead                                                 | Yes                                                 |
| Previous advisor                                 | Advisor from the policy booked lead                                                    | No                                                  |

# Cycle

| **IMCRM Sections**                               | **Fields**                                           | **Notes**                                            | **Required** |
| ------------------------------------------------ | ---------------------------------------------------- | ---------------------------------------------------- | ------------ |
| Cycle detail                                     | Customer type                                        | Based on AML screening                               | No           |
| Advisor                                          | Based on the upload and update file                  | No                                                   |
| Quote details                                    | Cycle make                                           | Cycle make from the policy booked lead               | No           |
| Cycle model                                      | Cycle model from the policy booked lead              | No                                                   |
| Year of manufacture                              | Year of manufacture from the policy booked lead      | No                                                   |
| Purchased of value (AED)                         | Purchased of value (AED) from the policy booked lead | No                                                   |
| Accessories                                      | Accessories from the policy booked lead              | No                                                   |
| Has accident                                     | Has accident from the policy booked lead             | No                                                   |
| Has good condition                               | Has good condition from the policy booked lead       | No                                                   |
| Customer profile<br><br><br><br><br><br><br><br> | First name                                           | First name from the policy booked lead               | Yes          |
| Last name                                        | Last name from the policy booked lead                | Yes                                                  |
| Insured first name                               | Insured first name from the policy booked lead       | Yes                                                  |
| Insured last name                                | Insured last name from the policy booked lead        | Yes                                                  |
| Mobile number                                    | Mobile number from the policy booked lead            | Yes                                                  |
| Email                                            | Email from the policy booked lead                    | Yes                                                  |
| Nationality                                      | Nationality from the policy booked lead              | Yes                                                  |
| Date of birth                                    | Date of birth from the policy booked lead            | Yes                                                  |
| Gender                                           | Gender from the policy booked lead                   | No                                                   |
| Last year's policy details                       | Renewal batch number                                 | Based on the previous policy expiry date (automated) | Yes          |
| Previous policy number                           | Policy number from the policy booked lead            | Yes                                                  |
| Previous policy expiry date                      | Expiry date from the policy booked lead              | Yes                                                  |
| Previous policy premium                          | Total price from the policy booked lead              | No                                                   |
| Previous policy start date                       | Start date from the policy booked lead               | Yes                                                  |
| Previous advisor                                 | Advisor from the policy booked lead                  | No                                                   |

# Yacht

| **IMCRM Sections**                               | **Fields**                                      | **Notes**                                            | **Required** |
| ------------------------------------------------ | ----------------------------------------------- | ---------------------------------------------------- | ------------ |
| Cycle detail                                     | Customer type                                   | Based on AML screening                               | No           |
| Advisor                                          | Based on the upload and update file             | No                                                   |
| Quote details                                    | Boat details                                    | Boat details from the policy booked lead             | No           |
| Engine details                                   | Engine details from the policy booked lead      | No                                                   |
| Claim experience                                 | Claim experience from the policy booked lead    | No                                                   |
| Sum insured                                      | Sum insured from the policy booked lead         | No                                                   |
| Use                                              | Use from the policy booked lead                 | No                                                   |
| Operator experience                              | Operator experience from the policy booked lead | No                                                   |
| Customer profile<br><br><br><br><br><br><br><br> | First name                                      | First name from the policy booked lead               | Yes          |
| Last name                                        | Last name from the policy booked lead           | Yes                                                  |
| Insured first name                               | Insured first name from the policy booked lead  | Yes                                                  |
| Insured last name                                | Insured last name from the policy booked lead   | Yes                                                  |
| Mobile number                                    | Mobile number from the policy booked lead       | Yes                                                  |
| Email                                            | Email from the policy booked lead               | Yes                                                  |
| Nationality                                      | Nationality from the policy booked lead         | Yes                                                  |
| Date of birth                                    | Date of birth from the policy booked lead       | Yes                                                  |
| Gender                                           | Gender from the policy booked lead              | No                                                   |
| Last year's policy details                       | Renewal batch number                            | Based on the previous policy expiry date (automated) | Yes          |
| Previous policy number                           | Policy number from the policy booked lead       | Yes                                                  |
| Previous policy expiry date                      | Expiry date from the policy booked lead         | Yes                                                  |
| Previous policy premium                          | Total price from the policy booked lead         | No                                                   |
| Previous policy start date                       | Start date from the policy booked lead          | Yes                                                  |
| Previous advisor                                 | Advisor from the policy booked lead             | No                                                   |

# Group Medical

| **IMCRM Sections**          | **Fields**                                           | **Notes**                                            | **Required** |
| --------------------------- | ---------------------------------------------------- | ---------------------------------------------------- | ------------ |
| Group medical lead detail   | Customer type                                        | Based on AML screening                               | No           |
| First name                  | First name from the policy booked lead               | No                                                   |
| Last name                   | Last name from the policy booked lead                | No                                                   |
| Mobile number               | Mobile number from the policy booked lead            | No                                                   |
| Email                       | Email from the policy booked lead                    | No                                                   |
| Company name                | Company name from the policy booked lead             | No                                                   |
| Advisor                     | Based on the upload and update file                  | No                                                   |
| Number of employees         | Number of employees from the policy booked lead      | No                                                   |
| Business insurance type     | Group Medical                                        | Yes                                                  |
| Brief details               | Brief details from the policy booked lead            | No                                                   |
| Gender                      | Gender from the policy booked lead                   | No                                                   |
| Entity profile              | First name                                           | First name from the policy booked lead               | Yes          |
| Last name                   | Last name from the policy booked lead                | Yes                                                  |
| Mobile number               | Mobile number from the policy booked lead            | Yes                                                  |
| Email                       | Email from the policy booked lead                    | Yes                                                  |
| Company name                | Company name from the policy booked lead             | No                                                   |
| Trade license no            | Trade license no from the policy booked lead         | No                                                   |
| Emirates of registration    | Emirates of registration from the policy booked lead | No                                                   |
| Company address             | Company address from the policy booked lead          | No                                                   |
| Industry type               | Industry type from the policy booked lead            | No                                                   |
| Entity type                 | Entity type from the policy booked lead              | No                                                   |
| Risk category               | Based on KYC once completed                          | No                                                   |
| UBO details                 | UBO details table                                    | UBO details table from the policy booked lead        | No           |
| Last year's policy details  | Renewal batch number                                 | Based on the previous policy expiry date (automated) | Yes          |
| Previous policy number      | Policy number from the policy booked lead            | Yes                                                  |
| Previous policy expiry date | Expiry date from the policy booked lead              | Yes                                                  |
| Previous policy premium     | Total price from the policy booked lead              | No                                                   |
| Previous policy start date  | Start date from the policy booked lead               | Yes                                                  |
| Previous advisor            | Advisor from the policy booked lead                  | No                                                   |

# Corpline

| **IMCRM Sections**          | **Fields**                                           | **Notes**                                            | **Required** |
| --------------------------- | ---------------------------------------------------- | ---------------------------------------------------- | ------------ |
| Business quote detail       | Customer type                                        | Based on AML screening                               | No           |
| Company name                | Company name from the policy booked lead             | No                                                   |
| Company address             | Company address from the policy booked lead          | No                                                   |
| Policy number               | Policy number from the policy booked lead            | No                                                   |
| Advisor                     | Based on the upload and update file                  | No                                                   |
| Price                       | Price from the policy booked lead                    | No                                                   |
| Number of employees         | Number of employees from the policy booked lead      | No                                                   |
| Business insurance type     | Business insurance type from the policy booked lead  | Yes                                                  |
| Brief details               | Brief details from the policy booked lead            | No                                                   |
| Gender                      | Gender from the policy booked lead                   | No                                                   |
| Entity profile              | First name                                           | First name from the policy booked lead               | Yes          |
| Last name                   | Last name from the policy booked lead                | Yes                                                  |
| Mobile number               | Mobile number from the policy booked lead            | Yes                                                  |
| Email                       | Email from the policy booked lead                    | Yes                                                  |
| Company name                | Company name from the policy booked lead             | No                                                   |
| Trade license no            | Trade license no from the policy booked lead         | No                                                   |
| Emirates of registration    | Emirates of registration from the policy booked lead | No                                                   |
| Company address             | Company address from the policy booked lead          | No                                                   |
| Industry type               | Industry type from the policy booked lead            | No                                                   |
| Entity type                 | Entity type from the policy booked lead              | No                                                   |
| Risk category               | Based on KYC once completed                          | No                                                   |
| UBO details                 | UBO details table                                    | UBO details table from the policy booked lead        | No           |
| Last year's policy details  | Renewal batch number                                 | Based on the previous policy expiry date (automated) | Yes          |
| Previous policy number      | Policy number from the policy booked lead            | Yes                                                  |
| Previous policy expiry date | Expiry date from the policy booked lead              | Yes                                                  |
| Previous policy premium     | Total price from the policy booked lead              | No                                                   |
| Previous policy start date  | Start date from the policy booked lead               | Yes                                                  |
| Previous advisor            | Advisor from the policy booked lead                  | No                                                   |

# FRD: IMCRM - Upload and Update Enhancement

## **Version Table:**

| **Date**       | **Version** | **Modified by** | **Comments** |
| -------------- | ----------- | --------------- | ------------ |
| 17th July 2023 | 1.0         | April           |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/8678cxu51](https://app.clickup.com/t/8678cxu51)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             | Hitesh<br>Paula<br>Hussain                                                           |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | April                                                                                |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

##

## **Background:**

As of the moment, the validation used by the system to match the upload and update, and the created leads via upload and create process, is by checking the previous policy number and policy expiry date.

These information as well as the contact information of the customer have to be replaced by the Ref-ID created by the system.

## **A. Use-case or User story: Template update**

As IM, I want to remove the policy number from the upload and update process for data security.

## **A. Requirements: Template update**

- Upload and update template has to be revised by removing the following fields and replace them with Ref-ID:
  - Customer name
  - Customer email
  - Customer mobile
  - Insurance Type
  - Insurance Provider
  - Policy number
  - Policy end date
- Ref-IDs will be created upon creating the renewal leads FRD: IMCRM Renewals - System to Upload Renewals using API ([https://doc.clickup.com/d/h/232ey-100498/4b6224d6dfce68b/232ey-30047](https://doc.clickup.com/d/h/232ey-100498/4b6224d6dfce68b/232ey-30047))

## **B. Use-case or User story: Skip plans for non-motor**

As a manager, I want to allocate the renewal customers to the assigned renewal advisors and renewal batch number.

## **B. Requirements: Skip plans for non-motor**

- Create a skip plans version that will assign a renewal lead to an advisor and batch (excluding Health)
- Required fields will only be the Ref-ID, advisor email, and batch
- If upload and update (skip plans - no) is required to be done later on, then it must be allowed
- Same process flow:
  - Upload and update (skip plans - yes for non motor)
  - Fetch plans
  - Send emails - kindly refer to this FR FRD: Renewal Reminder for Travel, Home, Pet, Bike, Cycle, Medical, Business, Yacht ([https://doc.clickup.com/d/h/232ey-31047/3cb1fd8dfb72cfa/232ey-62287](https://doc.clickup.com/d/h/232ey-31047/3cb1fd8dfb72cfa/232ey-62287))

## **Assumptions:**

Assuming all policy information are correct prior to creating the renewal lead.

## **Additional resources:**

FRD: IMCRM - Search Legacy Policies ([https://doc.clickup.com/d/h/232ey-100498/4b6224d6dfce68b/232ey-58947](https://doc.clickup.com/d/h/232ey-100498/4b6224d6dfce68b/232ey-58947))

# FRD: Enhancement: Upload and update: Do not trigger API if the lead does not have make and model

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 21.10.2024 | 1.0         | April           |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86eqhdhqg](https://app.clickup.com/t/86eqhdhqg)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestor(s)** | Shahrukh                                                                             |     |
| **Approvers**             | Hussain<br>Paula                                                                     |     |
| **BA**                    | April                                                                                |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

##

## **A. Use-case or User story: Upload and update validation**

As IM, I want the system to validate that both the vehicle make and model are present before triggering the plans API, to prevent unnecessary provider API calls and reduce system load.

## **A. Requirements: Upload and update validation**

1. The system must validate that both the vehicle's make and model are present before proceeding to call the plans or ratings API.
2. If the vehicle make or model is missing during the upload and update process, the system must prevent triggering the plans or ratings API.
   1. This should not stop the send OCB email process.

## **Additional resources:**

![](https://t2197982.p.clickup-attachments.com/t2197982/f8dbc696-cecd-44ea-87f2-7b7a314eb353/image.png)

# FRD: Enhancement: Add a validation in the customer email field

## **Version Table:**

| **Date**    | **Version** | **Modified by** | **Comments** |
| ----------- | ----------- | --------------- | ------------ |
| 18 Mar 2025 | 1.0         | April           |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86erwbja5](https://app.clickup.com/t/86erwbja5)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestor(s)** |                                                                                      |     |
| **Approvers**             | Hitesh                                                                               |     |
| **BA**                    | April                                                                                |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

## **Background:**

Currently, there are no validations in place, allowing invalid email IDs to be used, which negatively impacts the process flow.

## **A. Use-case or User story:**

As IM, I want to ensure that only valid email IDs are used when creating renewal leads.

## **A. Requirements:**

- Implement a validation in the **existing "Upload and Create"** and **"Upload and Update"** processes to ensure that only valid email IDs are entered.
  - If an invalid value (anything other than a properly formatted email ID) is entered, the system should throw a validation error: **"Invalid customer email ID entered."**
  - Properly formatted email ID includes the following:
    - Local part (e.g., `name123`)
    - "@" symbol
    - Domain part (e.g., [`example.com`](http://example.com))

## **Additional resources:**

![](https://t2197982.p.clickup-attachments.com/t2197982/db1d9d48-6522-42cd-bafb-36adf748caa3/image.png)

# Enhancement: Upload and Update for Non-motor (except Health)

## **Meta Details:**

| **Enhancement Name** | Enhancement: Upload and Update for Non-motor | **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86evw1erq](https://app.clickup.com/t/86evw1erq)) |
| -------------------- | -------------------------------------------- | ------------------------- | ------------------------------------------------------------------------------------ |
| **Prepared by**      | April Pascual                                | **Designation**           | DT Manager                                                                           |
| **Reviewed by**      | Mohammad Asad Alam                           | **Designation**           | Head of Product and CX                                                               |
| **Reviewed date**    | 26.12.2025                                   | **Status**                | Approved                                                                             |
| **Business Review**  |                                              | **Designation**           |                                                                                      |
| **Reviewed Date**    |                                              | **Status**                |                                                                                      |
| **Level 1 Approver** | Hitesh Motwani                               | **Designation**           | Deputy CEO                                                                           |
| **L1 Approval Date** | N/A                                          | **Status**                |                                                                                      |
| **Level 2 Approver** | Avinash Babur                                | **Designation**           | CEO                                                                                  |
| **L2 Approval Date** | N/A                                          | **Status**                |                                                                                      |
| **Version Control**  | 1.0                                          | **Status**                | WIP                                                                                  |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86evw1erq](https://app.clickup.com/t/86evw1erq)) |
| ------------------------- | ------------------------------------------------------------------------------------ |
| **Approvers**             |                                                                                      |
| **BA**                    | April Pascual                                                                        |
| **Version Control**       | \[Maintained in CU Document History\]                                                |

Please delete sections that you don't use

## **Background:**

This enhancement introduces a controlled **Upload and Update mechanism for "All other non-motor lines"**, allowing limited but essential bulk updates without impacting existing workflows or validations.

This is an **enhancement to the existing Non-Motor Upload & Update functionality**, designed to support renewal lead assignment at scale while maintaining strict controls.

## **Business Value Mapping**

\[**Every user story** must tie to a measurable value; e.g. “Reduces advisor manual work by X hrs/month”, “Expected to increase NPS by 10%”\]

| **KPI/Metric**                    | **Target/Description**                                      | **Type**   |
| --------------------------------- | ----------------------------------------------------------- | ---------- |
| Renewal lead eligibility accuracy | 100% updates applied only to renewal_upload leads           | Compliance |
| Data integrity incidents          | 0 unintended field updates outside Ref-ID and advisor email | Control    |

## **MoSCoW Prioritization Table**

| **Requirement**                                               | **Must/Should/Could/Won’t** | **Rationale (one line)**          |
| ------------------------------------------------------------- | --------------------------- | --------------------------------- |
| Add “All other non-motor lines” to Upload & Update LOB filter | Must                        | Enables controlled bulk updates   |
| Restrict template to Ref-ID and Advisor Email only            | Must                        | Prevents unintended data changes  |
| Validate lead source = renewal_upload                         | Must                        | Ensures renewals-only processing  |
| Ignore manually assigned renewal leads                        | Must                        | Preserves manager-led assignments |
| Enforce valid IMCRM advisor email                             | Must                        | Prevents assignment errors        |
| Allow manager manual reassignment post-upload                 | Should                      | Maintains operational flexibility |

## **A. Use-case or User story: Upload and update for non-motor**

**As a** Renewals Manager,
**I want to** bulk upload and update advisor assignments for renewal leads under all other non-motor lines using Ref-ID,
**so that** reassignment can be handled efficiently while ensuring only eligible renewal leads are updated.

## **A. Requirements: Upload and update for non-motor**

1. Add a new dropdown value "All other non-motor lines" under Line of Business in the Non-Motor Upload and Update page. Applicable for the following lines:
   1. Pet
   2. Cycle
   3. Yacht
   4. Group Medical
   5. Corpline
2. When selected, the [upload and update template](https://docs.google.com/spreadsheets/d/1V_RsnSvjq5gtBKB2a8_6uy2YLJijHZ1qR1GgUvmY0FI/edit?gid=0#gid=0) must include only two fields:
   1. Ref-ID (mandatory, Ref-ID only)
   2. Advisor email (mandatory, must be a valid IMCRM user)
3. Validations:
   1. Upload and update process must apply only if lead source = renewal_upload.
   2. If the renewal lead is already manually assigned, ignore the upload record.
      1. The lead can still be reassigned manually by the manager per existing IMCRM behavior.

## **Additional resources:**

Private ([https://app.clickup.com/2197982/docs/232ey-46718/232ey-284598](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284598))

# FRD: Renewal reminder for Non-GCC, Bike, Company Vehicle or under a Company Name

## **Version Table:**

| **Date**      | **Version** | **Modified by** | **Comments** |
| ------------- | ----------- | --------------- | ------------ |
| 11th May 2023 | 1.0         | April           |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/8677zvx09](https://app.clickup.com/t/8677zvx09)) |         |
| ------------------------- | ------------------------------------------------------------------------------------ | ------- |
| **Sprint**                | \[Project manager to fill\]                                                          |         |
| **Business Requestors**   | Jeff                                                                                 |         |
| **Approvers**             | Jeff<br>Jake<br>Veeral<br>Paula<br>Hussain<br>Hitesh                                 |         |
| **Budget Approval**       |                                                                                      |         |
| **CTO**                   |                                                                                      |         |
| **CDTO**                  | Paula                                                                                | 20 mins |
| **CMO**                   |                                                                                      |         |
| **CPO - UI/UX**           |                                                                                      |         |
| **Content**               |                                                                                      |         |
| **PM**                    |                                                                                      |         |
| **BA/DTM/Champion**       | April                                                                                | 1 hour  |
| **QA**                    | \[Project manager to fill\]                                                          |         |
| **CPO**                   |                                                                                      |         |
| **Developers**            | \[Project manager to fill\]                                                          |         |
| **To Inform**             |                                                                                      |         |

## **Background:**

As of the moment, the reminders for Non-GCC, Bike, Company Vehicle or under a Company Name are being sent using SendBlaster.

The objective is to send all reminders and allocate these leads from IMCRM.

## **Use-case or User story:**

As IM, I want the reminders for Non-GCC, Bike, Company Vehicle or under a Company Name to be sent out from IMCRM.

## **Requirements:**

- Create another version of skip plans for an additional scenario called "**Skip Plans for Non GCC, Bike, Company Vehicles"**
  ![](https://t2197982.p.clickup-attachments.com/t2197982/f1ecef6f-d6fa-4d41-bbb7-e0da207e1037/image.png)
- CQF process will still be followed but needs some modification
- Skip Plans for renewals that cannot be quoted to have the following conditions:
  - Upload and Update will be used ONLY to assign the renewal advisor and renewal batch number
  - Fields in the upload and update template to be updated from mandatory to optional:
    - Car make
    - Car model
    - Model year
    - Date of birth
    - Driving experience
    - Nationality
    - Claims history
    - No Claims letter
    - Emirate of registration
  - Fetch plans must not run
  - Send emails
    - [OCB template for 0 quote scenario](https://doc.clickup.com/2197982/p/h/232ey-38987/e90d710818b111f) must be sent out to the customer

## **Additional resources:**

[https://doc.clickup.com/2197982/p/h/232ey-38987/e90d710818b111f](https://doc.clickup.com/2197982/p/h/232ey-38987/e90d710818b111f)

# OCB template for 0 quote scenario

Subject line: (Customer Name)'s car insurance renewal with Alfred

From: [advisorfirstname.lastname@renewals.insurancemarket.ae](mailto:advisorfirstname.lastname@renewals.insurancemarket.ae)
To: customer's email ID(s)
Reply-to: advisor's email ID
CC: advisor's email ID

![](https://t2197982.p.clickup-attachments.com/t2197982/cf50fe0e-21a5-479f-b9fd-14f9e18f6295/image.png)

# FRD: IMCRM - Search Filter Enhancement

## **Version Table:**

| **Date**       | **Version** | **Modified by** | **Comments** |
| -------------- | ----------- | --------------- | ------------ |
| 14th July 2023 | 1.0         | April           |              |
| 25th July 2023 | 1.1         | April           | <br>         |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/8678cxtv7](https://app.clickup.com/t/8678cxtv7)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestors**   |                                                                                      |     |
| **Approvers**             | Paula<br>Hitesh<br>Hussain                                                           |     |
| **Budget Approval**       |                                                                                      |     |
| **CTO**                   |                                                                                      |     |
| **CDTO**                  |                                                                                      |     |
| **CMO**                   |                                                                                      |     |
| **CPO - UI/UX**           |                                                                                      |     |
| **Content**               |                                                                                      |     |
| **PM**                    |                                                                                      |     |
| **BA/DTM/Champion**       | April                                                                                |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **CPO**                   |                                                                                      |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

##

## **A. Use-case or User story: Managers exporting renewals**

As a manager, I want to export the list of renewal customers so I can allocate these customers to the renewal advisors.

## **A. Requirements: Managers exporting renewals**

- Create Search as a separate module
- Filters to be added:
  - Policy expiry date (date range)
  - Product (dropdown list of all products)
  - Ref-ID
  - Policy number
  - Email ID
  - Phone number
- Dynamic fields to be shown based on product selection \* If Motor insurance, then we can keep the current fields
  ![](https://t2197982.p.clickup-attachments.com/t2197982/1f5a246d-bc3f-42e5-9227-e2527c31fc5d/image.png)
-       *   If non-motor, then only show the following information:
          *   Ref-ID
          *   Product
          *   Subtype (Business Insurance)
          *   Currently insured with
          *   Policy start date
          *   Policy expiry date
          *   Gross premium
  ![](https://t2197982.p.clickup-attachments.com/t2197982/90eb41c9-363b-4cb5-a5a5-f4d9458f8d9e/image.png)
- Manager must have an option to export the renewal leads from IMCRM
  _ Leads to be shown are limited to lead source Renewal_upload only
  _ Export file must NOT contain any contact information of the customer
  _ Create a permission export_nocontactinfo
  _ Export file must contain the following:
  _ Ref-ID
  _ Customer name
  _ Insurance provider
  _ Product
  _ Subtype (Business Insurance)
  _ Policy start date
  _ Policy expiry date
  _ Gross premium
  _ Commission
  _ Previous advisor
  ![](https://t2197982.p.clickup-attachments.com/t2197982/c6f86294-4923-4cdf-a4b0-97574c6e9fde/image.png)

## **Additional resources:**

FRD: Add search filter ([https://doc.clickup.com/d/h/232ey-20587/8d7ad220fb056b0/232ey-49127](https://doc.clickup.com/d/h/232ey-20587/8d7ad220fb056b0/232ey-49127))
Private ([https://app.clickup.com/2197982/docs/232ey-27607/232ey-49227](https://app.clickup.com/2197982/docs/232ey-27607/232ey-49227))

# Enhancement: Renewals Search: Include additional fields required for allocations and audit

## **Meta Details:**

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86euf3ufe](https://app.clickup.com/t/86euf3ufe)) |
| ------------------------- | ------------------------------------------------------------------------------------ |
| **Approvers**             |                                                                                      |
| **BA**                    |                                                                                      |
| **Version Control**       | \[Maintained in CU Document History\]                                                |

Please delete sections that you don't use

## **Background:**

Alongside the CQF automation, allocations will be managed separately. Managers will continue downloading the allocation file from IMCRM, but without contact details.

To identify multiple policies belonging to the same customer, a **unique identifier** is required.

Additionally, **PC tagging** is needed as it's factored into the allocation logic.

For audit purposes against the insurer list, we also require the **policy number**.

## **A. Use-case or User story: Additional fields**

**As a** renewals manager, **I want to** include the customer ID, PC tagging, and policy number in the allocation file, **so that** multiple policies belonging to the same customer can be identified and allocated to a single advisor, while ensuring policies are audited accurately against the insurer list.

## **A. Requirements: Additional fields**

1. From the existing functionalities FRD: IMCRM - Search Filter Enhancement ([https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4](https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4))
2. Add additional fields in the export file:
   1. Customer ID
      1. Numeric and unique identifier
   2. PC Tag
      1. Yes or No based on the PC qualifications
   3. Policy number
      1. This is the previous policy number from the last year's policy details section

### **Low fidelity mock-up(s):**

Motor:
![](https://t2197982.p.clickup-attachments.com/t2197982/8a9aa217-876d-4033-a35f-d67e455bb91e/image.png)

Non-motor except Business:
![](https://t2197982.p.clickup-attachments.com/t2197982/cbdba374-a12a-402a-9968-b347d39adf9a/image.png)

Business:
![](https://t2197982.p.clickup-attachments.com/t2197982/ee393d76-2f63-4695-a181-6388b61dba8e/image.png)

## **Additional resources:**

FRD: IMCRM - Search Filter Enhancement ([https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4](https://doc.clickup.com/d/h/232ey-58987/0d1ef9d0d51a7f4))

# Enhancement: Add Customer Level PC Tag

## **Meta Details:**

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86ev44vpa](https://app.clickup.com/t/86ev44vpa)) |
| ------------------------- | ------------------------------------------------------------------------------------ |
| **Approvers**             |                                                                                      |
| **BA**                    | April Pascual                                                                        |
| **Version Control**       | \[Maintained in CU Document History\]                                                |

Please delete sections that you don't use

## **Background:**

At the moment, the export under Renewals Search shows PC tag at lead level. We need the PC tag at customer level in order to utilize this data for allocation purposes.

## **A. Use-case or User story:**

As Renewals Manager, I want to know if a PC customer has other renewal leads that may or may not qualify for PC tagging so that I can allocate the leads accordingly as per the agreed allocation logic.

## **A. Requirements:**

1. [](https://app.clickup.com/2197982/docs/232ey-46718/232ey-300898?block=block-99e8b143-e45b-40ef-b1e3-5e6b14263157)
   1. Update the column name to Lead Level PC Tag.
      1. Values to be Yes or No
      2. Check lead level
2. Add another column named as Customer Level PC Tag.
   1. Values to be Yes or No
   2. Check customer level
3. The exported file should also be in excel format instead of CSV.

## **Additional resources:**

Private ([https://app.clickup.com/t/86ev35enp](https://app.clickup.com/t/86ev35enp))
Private ([https://app.clickup.com/t/86er5by5p](https://app.clickup.com/t/86er5by5p))

# Enhancement: Renewals Search and Export

## **Meta Details:**

| **Enhancement Name** | Enhancement: Renewals Search and Export | **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86evw1hqc](https://app.clickup.com/t/86evw1hqc)) |
| -------------------- | --------------------------------------- | ------------------------- | ------------------------------------------------------------------------------------ |
| **Prepared by**      | April Pascual                           | **Designation**           | DT Manager                                                                           |
| **Reviewed by**      | Mohammad Asad Alam                      | **Designation**           | Head of Product and CX                                                               |
| **Reviewed date**    | 26.12.2025                              | **Status**                | Approved                                                                             |
| **Business Review**  |                                         | **Designation**           |                                                                                      |
| **Reviewed Date**    |                                         | **Status**                |                                                                                      |
| **Level 1 Approver** | Hitesh Motwani                          | **Designation**           | Deputy CEO                                                                           |
| **L1 Approval Date** | N/A                                     | **Status**                |                                                                                      |
| **Level 2 Approver** | Avinash Babur                           | **Designation**           | CEO                                                                                  |
| **L2 Approval Date** | N/A                                     | **Status**                |                                                                                      |
| **Version Control**  | 1.0                                     | **Status**                | WIP                                                                                  |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86evw1hqc](https://app.clickup.com/t/86evw1hqc)) |
| ------------------------- | ------------------------------------------------------------------------------------ |
| **Approvers**             |                                                                                      |
| **BA**                    |                                                                                      |
| **Version Control**       | \[Maintained in CU Document History\]                                                |

Please delete sections that you don't use

## **Background:**

To improve clarity and reporting in the renewals process, the **Renewals Search** and export files are being enhanced. **Nationality** will be added, and **Previous Gross Premium** will be renamed to **Previous Total Price with VAT** for better understanding and decision-making.

## **Business Value Mapping**

\[**Every user story** must tie to a measurable value; e.g. “Reduces advisor manual work by X hrs/month”, “Expected to increase NPS by 10%”\]

| **KPI/Metric** | **Target/Description**                                                    | **Type** |
| -------------- | ------------------------------------------------------------------------- | -------- |
| Data clarity   | Increase clarity in renewals export files by 100% for allocation purposes | Accuracy |

## **MoSCoW Prioritization Table**

| **Requirement**                                               | **Must/Should/Could/Won’t** | **Rationale (one line)**                       |
| ------------------------------------------------------------- | --------------------------- | ---------------------------------------------- |
| Add Nationality to renewals search and export                 | Must                        | Required for accurate advisor allocation logic |
| Rename Previous Gross Premium → Previous Total Price with VAT | Must                        | Provides consistency in the terminology used   |

## **A. Use-case or User story: Renewals Search and Export Enhancement**

**As a** Renewals Manager,
**I want to** include **Nationality** in the renewals search and export, rename **Previous Gross Premium** to **Previous Total Price with VAT**, and map this value to **Previous Commission** in the export file,
**so that** the data is clearer, consistent, and easier to analyse for reporting and decision-making.

## **A. Requirements: Renewals Search and Export Enhancement**

1. Add Nationality in the renewals search columns as well as the export file.
2. Rename the Previous Gross Premium to Previous Total Price with VAT for more clarity.
3. The value entered in this field [](https://app.clickup.com/2197982/docs/232ey-46718/232ey-284598?block=block-8bb43ea1-4b49-49f5-8c95-e01705833bc6)from upload and create should be mapped to the Previous Commission in the renewal search export file.

# FRD: Enhancement: Renewals Module: Add a validation to check only renewal_upload lead source

## **Version Table:**

| **Date**   | **Version** | **Modified by** | **Comments** |
| ---------- | ----------- | --------------- | ------------ |
| 6 Nov 2024 | 1.0         | April           |              |

| **List or ClickUp tasks** | Private ([https://app.clickup.com/t/86eqfum60](https://app.clickup.com/t/86eqfum60)) |     |
| ------------------------- | ------------------------------------------------------------------------------------ | --- |
| **Sprint**                | \[Project manager to fill\]                                                          |     |
| **Business Requestor(s)** |                                                                                      |     |
| **Approvers**             | Hussain<br>Paula<br>Hitesh                                                           |     |
| **BA**                    | April                                                                                |     |
| **QA**                    | \[Project manager to fill\]                                                          |     |
| **Developers**            | \[Project manager to fill\]                                                          |     |
| **To Inform**             |                                                                                      |     |

Please delete sections that you don't use

##

## **Background:**

There is a validation in place on IMCRM that prevents the creation of a renewal lead if the previous policy number and previous policy expiry date already exist. However, we have introduced another lead source called _Insly_ to handle policy endorsements for policies booked through Insly, where the previous policy number and expiry date are displayed.

Currently, the validation applies to all leads in IMCRM, regardless of the lead source.

A new condition needs to be added: **the validation should only be applied to leads with the lead source "renewal_upload."**

## **A. Use-case or User story:**

As IM, I want to ensure that the validations for the 'upload and create' and 'upload and update' processes are applied only to leads with the lead source "renewal_upload."

## **A. Requirements:**

1. Upload and Create:
   1. This validation error "Quote already created for this policy number, use upload and update." should only be triggered if the lead with the lead source "renewal_upload" exists with the same previous policy number and previous policy expiry date.
   2. Current Issue: The validation prevents the creation of a renewal lead when a lead with the lead source Insly exists with the same previous policy number and previous policy expiry date.
   3. Expected Behavior: No validation error should occur if an Insly lead exists with the same previous policy number and previous policy expiry date.
2. Upload and Update:
   1. Update Condition: The lead with the same previous policy number and previous policy expiry date should only be updated if it has the lead source "renewal_upload".
   2. Conflict Handling: If both renewal_upload and Insly leads exist with the same previous policy number and previous policy expiry date., only the lead with the "renewal_upload" lead source should be updated.
   3. No Update for Insly Leads: The system should not update the lead with the Insly lead source if it has a matching previous policy number and previous policy expiry date with a renewal_upload lead.
3. No Validation for Insly Leads: Do not apply any validation for leads with the lead source Insly. These leads, which have been moved to IMCRM, should be excluded from validation checks related to previous policy number and previous policy expiry date.

# Enhancement: Last Year's Policy Details section edit

## **Meta Details:**

| **List or ClickUp tasks** | Enhancement: Last Year's Policy Details section edit ([https://share.clickup.com/t/h/86ev16wa3/7F1JAC67DH5HWNC](https://share.clickup.com/t/h/86ev16wa3/7F1JAC67DH5HWNC)) |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Approvers**             |                                                                                                                                                                           |
| **BA**                    | April Pascual                                                                                                                                                             |
| **Version Control**       | \[Maintained in CU Document History\]                                                                                                                                     |

Please delete sections that you don't use

## **A. Use-case or User story: Allow edit**

**As IMCRM user, I want to** edit or add missing/incorrect details in last year's policy fields, **so that** the renewal leads are accurately reflected in both the system and reports.

## **A. Requirements: Allow edit**

1. **Permission Control**
   1. New permission: `edit-last-year-details`
   2. Granted only upon HM's approval.
   3. Users without this permission cannot edit last year's policy details.
2. **Editable Fields**
   1. Renewal Batch
   2. Previous Policy Expiry Date
   3. Previous Policy Start Date
   4. Previous Policy Number
   5. Previous Policy Premium
   6. Previous Advisor
3. **Rules**
   1. If a value is **blank**, user can add it.
   2. If a value is **already filled**, user can edit it.
4. **Data Sync**
   1. Any changes must reflect in both:
      1. Last Year's Policy Details section
      2. Lead List view
5. **Non-Motor Auto Rule**
   1. If _Previous Policy Expiry Date_ is updated manually, the **Renewal Batch** must auto-update accordingly for non-motor based on the [non-motor renewal batches](https://imcrm.alfred.ae/generic/renewal-batches?page=1&quote_type_id=-1)
6. **Validation Alignment**
   1. If _Previous Policy Expiry Date_ is updated **before an Upload & Update**, the updated date should be used in validation instead of the old one.

## **Additional resources:**

![](https://t2197982.p.clickup-attachments.com/t2197982/293acf9f-514a-41a9-90d1-5fee0054ecbc/image.png)
