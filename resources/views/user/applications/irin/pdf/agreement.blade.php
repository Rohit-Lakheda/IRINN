<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Standard IRINN Affiliation Agreement</title>
    <style>
        @page { margin: 1.4cm 1.3cm; }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 10.5pt;
            line-height: 1.35;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .logo-container {
            margin-bottom: 8px;
        }
        .logo-container img {
            width: 72px;
            height: auto;
            display: block;
        }
        h1 {
            font-size: 13pt;
            text-align: center;
            font-weight: bold;
            margin: 10px 0 14px;
            text-transform: uppercase;
        }
        .meta-note {
            font-size: 9pt;
            margin-bottom: 12px;
            text-align: justify;
            font-style: italic;
        }
        h2 {
            font-size: 11pt;
            font-weight: bold;
            margin: 12px 0 6px;
        }
        .subhead {
            font-weight: bold;
            margin-top: 8px;
        }
        p { margin: 0 0 6px; text-align: justify; }
        ul, ol { margin: 4px 0 8px 18px; padding-left: 14px; }
        li { margin-bottom: 4px; text-align: justify; }
        ul.disc { list-style: none; margin-left: 0; padding-left: 0; }
        ul.disc li { padding-left: 14px; position: relative; }
        ul.disc li::before { content: '*'; position: absolute; left: 0; }
        .prefill {
            border-bottom: 1px solid #000;
            min-height: 14px;
            padding: 0 2px 1px;
            display: block;
            margin: 2px 0 6px;
        }
        .prefill-inline {
            border-bottom: 1px solid #000;
            padding: 0 4px 1px;
        }
        .label { font-weight: bold; }
        .seal-row {
            margin-top: 14px;
            font-size: 9pt;
            width: 100%;
        }
        .seal-row table { width: 100%; border-collapse: collapse; }
        .seal-row td { width: 50%; vertical-align: top; padding: 4px; }
        .page-num {
            text-align: center;
            font-size: 9pt;
            margin-top: 10px;
            color: #333;
        }
        .agreement-page { page-break-after: always; }
        .agreement-page:last-of-type { page-break-after: auto; }
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; }
        .sig-table td { vertical-align: top; padding: 4px 8px 4px 0; width: 50%; }
        .blank-sig { min-height: 28px; border-bottom: 1px solid #000; margin: 4px 0; }
    </style>
</head>
<body>
{{-- Page 1 --}}
<div class="agreement-page">
    <div class="logo-container">
        @if(file_exists(public_path('images/nixi-logo.jpg')))
            <img src="{{ public_path('images/nixi-logo.jpg') }}" alt="NIXI">
        @elseif(file_exists(public_path('images/nixi-logo.png')))
            <img src="{{ public_path('images/nixi-logo.png') }}" alt="NIXI">
        @endif
    </div>

    <h1>Standard IRINN Affiliation Agreement</h1>
    <p class="meta-note">This document was generated on {{ $generated_at_short ?? $date }} for your convenience. Affiliate details below are prefilled from your application and KYC where available. Add stamp (if required), signatures, company seal, and witness particulars where indicated, then upload the executed copy.</p>

    <h2>General details</h2>
    <p class="subhead">Affiliate's details :</p>
    <p><span class="label">Name of organization:</span> <span class="prefill-inline">{{ $company_name ?? '—' }}</span></p>
    <p class="label">Address for notices and legal correspondence:</p>
    <p>a) Postal address:</p>
    <span class="prefill">{{ $address_line_1 ?? '—' }}</span>
    <span class="prefill">{{ $address_line_2 ?? '' }}</span>
    <span class="prefill">{{ $address_line_3 ?? '' }}</span>
    <p>b) Email address:</p>
    <span class="prefill">{{ $email_line_1 !== '' ? $email_line_1 : '—' }}</span>
    @if(filled($email_line_2 ?? null))
        <span class="prefill">{{ $email_line_2 }}</span>
    @else
        <span class="prefill">&nbsp;</span>
    @endif

    <p class="subhead" style="margin-top: 10px;">Following details to be inserted by IRINN:</p>
    <p><span class="label">Account name (as assigned by IRINN):</span></p>
    @if(filled($irinn_account_name ?? null))
        <span class="prefill">{{ $irinn_account_name }}</span>
    @else
        <span class="prefill">________________________________________</span>
        <span class="prefill">________________________________________</span>
    @endif
    <p><span class="label">Affiliation date:</span> <span class="prefill-inline" style="min-width: 200px;">______________________________</span></p>
    <p><span class="label">Renewal dates:</span> <span class="prefill-inline" style="min-width: 200px;">______________________________</span></p>

    <table class="seal-row"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 1 of 10 —</p>
</div>

{{-- Page 2 --}}
<div class="agreement-page">
    <p class="subhead">Recitals :</p>
    <p>A. IRINN is a division of The National Internet Exchange of India(hereinafter NIXI), which is a not for profit Company, incorporated under section 25 of the Companies Act, 1956.</p>
    <p>B. IRINN is committed to acting in accordance with the interests and wishes of its affiliation in pursuing the following objectives:</p>
    <ul class="disc">
        <li>To support IRINN affiliates in fulfilling their responsibilities as customers and end-users of Internet resources;</li>
        <li>To promote the representation of the IRINN Affiliation and the Internet community India by ensuring open and transparent communications and consensus- driven decision-making processes;</li>
        <li>To promote responsible management of Internet resources throughout India as well as the responsible development and operation of Internet infrastructures;</li>
        <li>To promote and advance technical policy development in relation to IRINN services, and to Internet resource management in general;</li>
        <li>To provide high-quality Internet resource management services to IRINN affiliates, namely resource allocation services, registration and database services, and affiliation administration and support services;</li>
        <li>To assist Internet development activities in India relating to the above objectives.</li>
    </ul>
    <p>C. In view of above, IRINN accepts the Affiliate as an affiliate of the IRINN, and the Affiliate agrees to pay all relevant Affiliation Fees, IRINN and the Affiliate agree that the following terms will govern their relationship.</p>

    <p class="subhead">1 Term</p>
    <p class="subhead">1.1) Commencement &amp; term</p>
    <p>This agreement commences upon the affiliation date and is effective for one year from the affiliation date.</p>
    <p class="subhead">1.2 Renewals</p>
    <p>(a) The Affiliate may renew its affiliation by paying the IRINN the renewal fee by the due date. By renewing its affiliation, the Affiliate will be deemed to have agreed to the terms of the Standard IRINN Affiliation Agreement as it exists at the time of renewal.</p>
    <p>(b) If the Affiliate fails to renew its Affiliation within 30 days of the due date, then the IRINN may, by written notice to the Affiliate, revoke all of the Affiliate’s rights under the IRINN Policy.</p>

    <table class="seal-row"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 2 of 10 —</p>
</div>

{{-- Page 3 --}}
<div class="agreement-page">
    <p>(c) In those cases where IRINN has reason to believe that an affiliate is not using the address space as intended or is showing bad faith in following through on the associated obligation, IRINN reserves the right to not renew the license. In exercising its power to review such cases IRINN may request for additional information as may be required under the facts and circumstances of the case being reviewed.</p>
    <p class="subhead">1.3 Termination upon the event of insolvency</p>
    <p>In the event of insolvency the IRINN may immediately revoke all of the Affiliate’s rights under the IRINN Policy and terminate this Affiliation Agreement.</p>

    <p class="subhead">2 Rights and Obligations</p>
    <p class="subhead">2.1 The rights of Affiliates</p>
    <p>a) Affiliates are allowed to submit to IRINN Internet Resource request directly.</p>
    <p>b) Affiliates are allowed to submit comments and suggestions to IRINN on the process for application and administration of Internet Resources. IRINN will evaluate the practicability of the suggestions provided and may accept the same.</p>
    <p>c) IRINN will provide all relevant information related to the Internet Resources application and administration.</p>
    <p>d) Affiliates are allowed to submit changes in policy related to Internet Resources at local, regional and global level for consideration.</p>

    <p class="subhead">2.2 The obligations of Affiliates</p>
    <p>a) Affiliates are required to observe all applicable rules and regulations of IRINN and amendments thereof from time-to-time.</p>
    <p>b) Affiliates are required to register all relevant information of the delegated Internet Resources in IRINN's Whois database and update regularly to ensure that the information is correct and up-to-date.</p>
    <p>c) LIRs should guarantee normal functioning of DNS and the Routing table.</p>
    <p>d) Subsequent allocation will be provided when an organization (ISPs/LIRs/Ordinary internet users) satisfies the evaluation threshold of past address utilization in terms of the number of sites in units of/56 assignments for 1Pv6.</p>
    <p>e) The HD-Ratio [RFC 3194] is used to determine the utilization thresholds that justify the allocation of additional address as described below.</p>
    <p>f) The HD-Ratio value of 0.94 is adopted as indicating an acceptable address for justifying the allocation of additional address space.</p>

    <table class="seal-row"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 3 of 10 —</p>
</div>

{{-- Page 4 --}}
<div class="agreement-page">
    <p>Log (number of allocated objects)<br>HD=……………………………………………………………..<br>Log (maximum number of allocable objects)</p>
    <p>g) While applying for Internet Resources, affiliates should carry out an accurate evaluation of the exact need beforehand, and submit all required forms and related information. Also, affiliates should fully cooperate with IRINN on all necessary inspections and investigations, and provide required information.</p>
    <p>h) As and when APNIC changes the policies for Internet Resources management, IRINN has to follow the new policies and would be required to modify the existing rules. All affiliates are expected to adhere to the changes made on policies from time to time on the lines of APNIC policy within the stipulated time period.</p>
    <p>i) Affiliates are strictly prohibited to apply to both APNIC and IRINN for Internet Resources at the same time. In such an event IRINN reserves the Right to reject that application.</p>
    <p>j) To make the timely payments and taxes against all the invoices raised for providing the services.</p>
    <p>k) Not to provide any information to the IRINN which is false or misleading.</p>
    <p>(l) Inform the IRINN as soon as possible of any changes in material information including but not not limited to any KYC details which the Affiliate has previously supplied to the to the IRINN.</p>
    <p>(m) Comply with this agreement and all IRINN Policy and amendments thereof.</p>

    <p class="subhead">2.3 The rights of IRINN</p>
    <p>a) If the affiliates are not utilizing the assigned Internet Resources in accordance with the projections made at the time of application, IRINN has the right to take back the addresses after giving notice of 30 days.</p>
    <p>b) Affiliates are not allowed to engage in trading, buying, selling, barter exchange, hoarding/stockpiling, reservations etc. of the Internet Resources. If they are found engaging/engaged in such activities during any investigation, then all such affiliates are liable to forfeit all the allocated Internet Resources and related rights by IRINN under this Agreement, and will never be allowed to apply to IRINN for assignments again.</p>
    <p>c) To claim the payments and taxes against all the invoices raised for providing the services and in case of non-payment, to discontinue the rendering of services as per the defined procedure.</p>

    <table class="seal-row"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 4 of 10 —</p>
</div>

{{-- Page 5 --}}
<div class="agreement-page">
    <p class="subhead">2.4 The obligations of IRINN</p>
    <p>The IRINN must:</p>
    <p>i. IRINN shall keep confidential all the information submitted by the affiliates, and not release any information to outsiders without permission from the affiliates. However IRINN will disclose public information of affiliates in WHOIS database and share other documents to APNIC and Law enforcement/government authorities wherever required.</p>
    <p>ii. IRINN shall respond to affiliates' submissions within a reasonable period of time by email from the date of submissions.</p>
    <p>iii. IRINN may consider and accept reasonable opinions and suggestions from the applicants for improvements in IRINN policy document.</p>

    <p class="subhead">2.3 Liability and indemnity</p>
    <p>The Affiliate and the IRINN acknowledge that the following clauses 2.1 to 2.4 are essential in order to protect the affiliation as a whole and the IRINN’s ability to pursue the aims expressed in Recital B.</p>
    <p class="subhead">2.4</p>
    <p>a) To the extent permitted by law, the IRINN excludes all liability to the Affiliate arising out of or in connection with this agreement, the IRINN or delegated resources. This exclusion applies, without limitation, to all liability in contract or tort for actions or omissions of the IRINN and their employees, and consultants, but does not apply to liability arising directly from:</p>
    <p>1. Personal injury, including sickness and death;</p>
    <p>2. Loss of or damage to, tangible property (including both the property of the Affiliate and third party property);</p>
    <p>3. A breach of confidentiality or privacy to the extent caused or contributed to by any act or omission of the IRINN and their employees and consultants,</p>

    <table class="seal-row"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 5 of 10 —</p>
</div>

{{-- Page 6 --}}
<div class="agreement-page">
    <p>b) The Affiliate indemnifies the IRINN, NIXI and their employees &amp; members against the full amount of all expenses, losses, damages, and costs that the IRINN may incur as a result, whether directly or indirectly, of any breach of this agreement or any IRINN Policy by the Affiliate, its employees,</p>
    <p>c) In any event the IRINN's liability shall be limited to a maximum amount equivalent to the Member's service fee of the relevant financial year.</p>
    <p>For clarity, this clause 2.6 survives the termination of this agreement.</p>

    <p class="subhead">3. Notices, responses, and appeals</p>
    <p class="subhead">3.1 Notice</p>
    <p>a) If the IRINN reasonably believes that the Affiliate has breached this agreement or IRINN Policy then the IRINN must send a notice ("Notice") to the Affiliate.</p>
    <p>b) The Notice must:</p>
    <p>3.1.b.1 Describe the nature of the breach that the IRINN believes has occurred, and the course of action necessary to remedy the breach;</p>
    <p>3.1.b.2 Specify a reasonable period for the Affiliate to provide a response to the breach notice within the terms of clause 3.2, and to take the action necessary to remedy the breach; and</p>
    <p>3.1.b.3 Intended action of IRINN if the breach is not remedied within the stipulated time period.</p>

    <p class="subhead">3.2 Response to Notice</p>
    <p>The Affiliate must, by the time specified in clause 3.1(b)(2) send the IRINN a response to the Notice detailing that either:</p>
    <p>3.2.a The Affiliate has not committed the breach; or</p>
    <p>3.2.b The Affiliate has remedied the breach in accordance with clause 3.1(b)(1); or</p>
    <p>3.2.c Exceptional circumstances exist which justify the IRINN retracting or revising the Notice.</p>

    <table class="seal-row"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 6 of 10 —</p>
</div>

{{-- Page 7 --}}
<div class="agreement-page">
    <p class="subhead">3.3 Subsequent actions</p>
    <p>If the period specified in clause 3.1(b)(2) expires and, taking full account of any responses received under clause 3.2, the IRINN reasonably believes that the breach has not been remedied then the IRINN may, in its discretion, either send the Affiliate:</p>
    <p>3.3.a A subsequent Notice as described in section 3.1(b); or</p>
    <p>3.3.b A written notice immediately revoking some or all of the Affiliate's right under the IRINN Policy(including, without limitation, delegated resources); and/or immediately terminating this Affiliation Agreement.</p>
    <p>3.4 Notwithstanding anything contained in the previous provisions, IRINN has the right to terminate this agreement with immediate effect in case the affiliate is found indulging in any of the activities specified clause 2.3(b).</p>
    <p>3.5 Appeal to Corporate governance committee or any equivalent committee constituted by Board of NIXI if the Affiliate believes that the IRINN has failed to adequately consider all relevant circumstances or has acted unreasonably in sending a revocation notice under clause 3.3(b), then the Affiliate may appeal to the Corporate governance committee of NIXI, which must consider the appeal within 30 days from the date of receipt of such notice. If the Corporate Governance Committee decides that the Affiliate’s appeal is justified then the IRINN will withdraw the revocation notice.</p>
    <p class="subhead">3.6 Acknowledgment by Affiliate</p>
    <p>The Affiliate acknowledges that:</p>
    <p>3.6.1 If the Affiliate receives a notice under clauses 1.2(b)., 1.2(c) or 3.3(b) then the Affiliate must immediately cease using the delegated resources specified in the notice; and</p>
    <p>3.6.2 If the Affiliate fails to comply with clause 3.6.1, then IRINN may at its discretion terminate this Agreement.</p>

    <table class="seal-row"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 7 of 10 —</p>
</div>

{{-- Page 8 --}}
<div class="agreement-page">
    <p class="subhead">4 General</p>
    <p class="subhead">4.1 IRINN Policy</p>
    <p>The Affiliate agrees that:</p>
    <p>4.1.1 The IRINN Policy may be amended from time to time;</p>
    <p>4.1.2 Any such amendments are binding upon the Affiliate;</p>
    <p>4.1.3 IRINN Policy and amendments thereof shall form an integral part of and apply fully to this agreement; and</p>
    <p>4.1.4 If the affiliation is either terminated or not renewed, the Affiliate shall continue to be bound by the provisions of this agreement and IRINN Policy to the extent that the provisions relate to the use of resources or disputes arising from this agreement or IRINN Policy.</p>

    <p class="subhead">4.2 Governing law and Dispute Resolution</p>
    <p>4.2.1 This agreement is governed by and interpreted in accordance with the laws of India, excluding rules of private international that lead to the application of the laws of any other jurisdiction.</p>
    <p>4.2.2 In case of any dispute, the dispute shall be referred to a sole Arbitrator, the seat of Arbitrator shall be at Delhi and courts at Delhi shall have exclusive jurisdiction.</p>
    <p>4.2.3 On invocation of the Arbitration clause by either party, IRINN shall suggest a panel of three independent and distinguished persons (Retd Supreme Court &amp; High Court Judges only) from the panel of DIAC(Delhi International Arbitration Centre) to the other party to select anyone among them to act as the Sole Arbitrator. In the event of failure of the other party to select the Sole Arbitrator within 30 days from the receipt of the communication from IRINN suggesting the panel of arbitrators, the right of selection of the sole arbitrator by the other party shall stand forfeited and IRIN shall appoint the Sole Arbitrator from the suggested panel of three Arbitrators for adjudication of dispute( s). The fee of the Arbitrator shall be as per Fourth Schedule of the Arbitration Act.</p>

    <table class="seal-row"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 8 of 10 —</p>
</div>

{{-- Page 9 --}}
<div class="agreement-page">
    <p class="subhead">4.3 To the extent not excluded by law</p>
    <p>The rights, duties and remedies granted or imposed under the provisions of this agreement operate to the extent not excluded by law.</p>
    <p class="subhead">4.4 Order of precedence</p>
    <p>To the extent of any inconsistency, the terms and conditions contained within this agreement will prevail over any other Affiliation Agreement executed between the parties.</p>

    <table class="seal-row"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 9 of 10 —</p>
</div>

{{-- Page 10 --}}
<div class="agreement-page">
    <p class="subhead">Executed as an agreement:</p>

    <p><span class="label">Signed for :</span><br>{{ $company_name ?? '—' }}</p>
    <p style="font-size: 9.5pt; font-style: italic;">by its authorized representative: in the presence of:</p>

    <table class="sig-table">
        <tr>
            <td>
                <div class="blank-sig"></div>
                <p>Signature of authorized representative:</p>
                <div class="blank-sig"></div>
                <p>Full name of authorized representative:</p>
                <span class="prefill">{{ $signatory_rep_name !== '' ? $signatory_rep_name : '______________________________' }}</span>
                <p>Official company title of authorized representative</p>
                <span class="prefill">{{ $signatory_rep_title !== '' ? $signatory_rep_title : '______________________________' }}</span>
            </td>
            <td>
                <div class="blank-sig"></div>
                <p>Signature of Witness:</p>
                <div class="blank-sig"></div>
                <p>Full name of Witness:</p>
                <div class="blank-sig"></div>
            </td>
        </tr>
    </table>

    <p style="margin-top: 16px;"><span class="label">Signed for :</span><br>IRINN a Division of NIXI</p>
    <p style="font-size: 9.5pt; font-style: italic;">by its authorized representative: in the presence of:</p>

    <table class="sig-table">
        <tr>
            <td>
                <div class="blank-sig"></div>
                <p>Signature of authorized representative</p>
                <div class="blank-sig"></div>
                <p>Full name of authorized representative</p>
                <div class="blank-sig"></div>
                <p>Official company title of authorized representative</p>
            </td>
            <td>
                <div class="blank-sig"></div>
                <p>Signature of Witness</p>
                <div class="blank-sig"></div>
                <p>Full name of Witness</p>
                <div class="blank-sig"></div>
            </td>
        </tr>
    </table>

    <table class="seal-row" style="margin-top: 16px;"><tr>
        <td>Initials</td>
        <td style="text-align: right;">Initials Affiliate</td>
    </tr></table>
    <table class="seal-row"><tr>
        <td>IRINN a Division of NIXI Seal</td>
        <td style="text-align: right;">Company Seal</td>
    </tr></table>
    <p class="page-num">— 10 of 10 —</p>
</div>
</body>
</html>
