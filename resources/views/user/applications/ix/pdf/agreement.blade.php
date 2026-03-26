<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NIXI Connection Agreement</title>
    <style>
        @page {
            margin: 2cm 1.5cm;
        }
        
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
            position: relative;
        }
        
        .logo-container {
            position: absolute;
            top: 0.3cm;
            left: 0.3cm;
            z-index: 1000;
            width: 80px;
            height: auto;
        }
        
        .logo-container img {
            width: 100%;
            height: auto;
            max-width: 80px;
            display: block;
            object-fit: contain;
        }
        
        .content-wrapper {
            position: relative;
            padding-top: 0;
        }
        
        .header-note {
            font-size: 11pt;
            margin-bottom: 15px;
            font-weight: bold;
            text-decoration: underline;
        }
        
        h1 {
            font-size: 16pt;
            text-align: center;
            text-decoration: underline;
            margin: 15px 0;
            font-weight: bold;
        }
        
        h2 {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 15px;
            margin-bottom: 8px;
        }
        
        p {
            margin-bottom: 8px;
            text-align: justify;
        }
        
        .agreement-date {
            margin-bottom: 12px;
        }
        
        .party-details {
            margin: 12px 0;
        }
        
        .party-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .whereas {
            margin: 12px 0;
            text-align: justify;
        }
        
        .definition-term {
            font-weight: bold;
        }
        
        .definition-content {
            margin-left: 0;
            margin-bottom: 10px;
        }
        
        ol, ul {
            margin-left: 20px;
            margin-bottom: 8px;
            padding-left: 20px;
        }
        
        li {
            margin-bottom: 6px;
            text-align: justify;
        }
        
        .quoted-text {
            margin: 12px 0;
            padding-left: 10px;
        }
        
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .signature-block {
            width: 100%;
            margin-top: 25px;
        }
        
        .signature-left {
            width: 48%;
            float: left;
        }
        
        .signature-right {
            width: 48%;
            float: right;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11pt;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
        }
        
        .signature-field {
            margin-top: 5px;
            min-height: 20px;
            font-size: 11pt;
        }
        
        .no-break {
            page-break-inside: avoid;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="logo-container">
            @if(file_exists(public_path('images/nixi-logo.jpg')))
                <img src="{{ public_path('images/nixi-logo.jpg') }}" alt="NIXI Logo" style="width: 100%; height: auto; max-width: 80px; display: block;">
            @elseif(file_exists(public_path('images/nixi-logo.png')))
                <img src="{{ public_path('images/nixi-logo.png') }}" alt="NIXI Logo" style="width: 100%; height: auto; max-width: 80px; display: block;">
            @endif
        </div>
        
        {{-- Page 1 --}}
        <div class="header-note">
        *This form has NIXI connection agreements for both ISP and Non-ISP. Please fill whichever is applicable*
    </div>
    
    <h1>NIXI Connection Agreement</h1>
    
    <div class="agreement-date">
        <p>This Agreement is executed on this <strong>{{ now('Asia/Kolkata')->format('d') }}</strong> day of <strong>{{ now('Asia/Kolkata')->format('F') }}</strong>, <strong>{{ now('Asia/Kolkata')->format('Y') }}</strong>.</p>
    </div>
    
    <p><strong>Between</strong></p>
    
    <div class="party-details">
        <div class="party-name">{{ $companyName ?? ($user->fullname ?? '[Member Company Name]') }}</div>
        <div>With Registered Office at {{ $companyAddress ?? ($gstVerification->primary_address ?? '[Registered Address]') }}</div>
        <p>hereinafter referred to as "Member"</p>
    </div>
    
    <p><strong>AND</strong></p>
    
    <div class="party-details">
        <p><strong>The Chief Executive Officer, National Internet Exchange of India,</strong></p>
        <p>B-901, 9th Floor Tower B, World Trade Centre, Nauroji Nagar, New Delhi-110029 India</p>
        <p>hereinafter referred to as "NIXI"</p>
    </div>
    
    <div class="whereas">
        <p><strong>WHEREAS</strong> NIXI is established to be a neutral Internet Exchange facilitating the ISPs to interconnect with each other for efficient routing of domestic traffic.</p>
        <p><strong>AND WHEREAS</strong> the Member is an ISP duly licensed by DoT Government of India.</p>
        <p><strong>WHEREAS</strong> a member can also be a Non-ISP (CDN provider, Content player) member having valid AS number.</p>
        <p><strong>NOW</strong> the Member and NIXI agree as follows:</p>
    </div>
    
    <h2>1. Definitions:</h2>
    
    <p class="definition-content"><span class="definition-term">"ASN"</span> means Autonomous System Number, which is a globally unique identifier for Autonomous Systems. An Autonomous System (AS) is a group of IP networks having a single clearly defined routing policy, run by one or more network operators.</p>
    <p class="definition-content"><span class="definition-term">"AS"</span> shall mean Autonomous System.</p>
    <p class="definition-content"><span class="definition-term">"BGP"</span> means Border Gateway Protocol which is a protocol for exchanging routing information between gateway hosts (each with its own router) in a network of autonomous systems. BGP is often the protocol used between gateway hosts on the Internet. The routing table contains a list of known routers, the addresses they can reach, and a cost metric associated with the path to each router so that the best available route is chosen.</p>
    
    <p class="definition-content"><span class="definition-term">"Connection for member"</span> means the physical connection of the peering router/cache of member to the NIXI shared medium (e.g. LAN switch), which is not allowed to go beyond the premises of the respective Housing site.</p>
    
    <p class="definition-content"><span class="definition-term">"Core Area"</span> means the Room, cage or otherwise dedicated and separated rack space at the Housing Site where the NIXI equipment (e.g. LAN switch) is located.</p>
    
    <p class="definition-content"><span class="definition-term">"CDN"</span> means Content Delivery Network.</p>
    
    <p class="definition-content"><span class="definition-term">"Housing/Collocation Site for Member"</span> means the Physical location at which the NIXI is present, and where the peering router/server is located.</p>
    
    <p class="definition-content"><span class="definition-term">"Member"</span> means an ISP as well as a Non-ISP who wants to connect to NIXI and avail of its services under the terms and conditions of this agreement.</p>
    
    <p class="definition-content"><span class="definition-term">"Member Equipment"</span> means any equipment owned by, leased to, or otherwise controlled by a Member, who is here referred to as the 'owner' irrespective of the actual method of control.</p>
    
    <p class="definition-content"><span class="definition-term">"ISP"</span> means an Internet Service Provider, having valid ISP License issued by DoT, Government of India. The term ISP shall include those Internet Service Providers also who have got their License amended for providing Internet Telephony.</p>
    
    <p class="definition-content"><span class="definition-term">"Non-ISP"</span> means CDN provider, Content player having valid AS Number.</p>
    
    <h2>2. Services provided by NIXI to Member</h2>
    <p>NIXI shall:</p>
    <ol type="a">
        <li>Facilitate the connection for member through collocation provider.</li>
        <li>Provide rack space to the member at its site.</li>
        <li>Allow access to the core area only to authorized personnel of member under super-vision and authorization of NIXI.</li>
        <li>Provide 24 x 7 watch of the member equipment through NIXI collocation provider.</li>
        <li>Facilitate installation & support services.</li>
        <li>Facilitate network management related services.</li>
        <li>Provide any other services as decided by NIXI management.</li>
    </ol>

    <h2>3. Responsibilities of Member:</h2>
    <p>Member shall:</p>
    <ol type="a">
        <li>make payments to NIXI as per the Financial Schedule as applicable to the Member by the due date. No refund of charges will be made under any circumstances.</li>
        <li>at his own cost ensure the faultless connection of its Network through Leased line (or any other connecting medium such as Radio link, VSAT, Fiber, ATM, Frame relay etc.) to the NIXI network, as per the Technical Requirements of NIXI.</li>
        <li>be solely responsible for all charges (including insurance, maintenance, repairs, configuration, operation etc) related to the Member Equipment.</li>
        <li>provide 24x7 operational contact details for the use of NIXI staff and other NIXI Members as required by NIXI.</li>
        <li>ensure that its usage of the Connection / NIXI's network is not detrimental in any way and does not cause damage to the NIXI network / infrastructure or to the usage of NIXI by the other Members or to the traffic exchange thereon.</li>
        <li>Insure its equipment (including third party insurance) while it is in the NIXI racks.</li>
        <li>Not hold NIXI responsible for the content of its traffic being transmitted through NIXI net- work.</li>
        <li>Be solely responsible for the member's own violation of the conditions of ISP License(s) issued by the (DoT) Department of Telecom, Government of India under which he is authorized to provide Internet Services and connect to NIXI (Applicable for ISP members Only).</li>
        <li>Non-ISP Member shall be solely responsible for violation of any of the prevailing the laws.</li>
    </ol>
    
    <h2>4. Connecting Rules at NIXI to be adhered by Member:</h2>
    <ol type="a">
        <li>Members shall have an ASN (Autonomous System Number) and use BGP4(+) for peering.</li>
        <li>The Autonomous System Number, which the Member provides to NIXI, should be vis- ible from the NIXI router.</li>
        <li>Member shall not handover any third party traffic and advertise its own traffic.</li>
        <li>Member shall handover its traffic to the NIXI PoP which is nearest to the destination of the traffic.</li>
        <li>Member may allow other ISP's to connect to NIXI and handover their traffic through its own facilities. Member in that case will advertise the ASN of such ISPs connecting through it. Member shall have its own agreement with the ISPs connected through it, requiring such ISPs to adhere to the terms and conditions of this Agreement.</li>
        <li>Member shall not use NIXI Services to carry out any illegal or unauthorized activities.</li>
        <li>Members will be allowed to do bilateral peering/transit amongst themselves without using the NIXI switches or any other NIXI networking equipment.</li>
    </ol>

    <h2>5. Peering Policy of NIXI</h2>
    <p>NIXI has adopted forced multilateral peering policy. Members shall not refuse to accept traffic from other members.</p>
    
    <h2>6. NON COMPETE</h2>
    <p>NIXI is a neutral Internet Exchange facilitating the members to interconnect with each other for efficient routing of domestic traffic and shall not compete with the business of other members.</p>
    
    <h2>7. Membership Status</h2>
    <p>It is clarified that submission of this Agreement and Connection form does not guarantee membership of NIXI. Membership shall be granted only after the application and agreement is approved by the Board of NIXI. Upon approval, the Member shall have all rights as provided in the NIXI Bye-Laws/Articles of Association including the right to offer candidature for election to the NIXI Board when permissible.</p>
    
    <h2>8. Warranties & Representations</h2>
    <p><strong>Member (ISP):</strong> Represents and warrants that it possesses a valid License from the Department of Telecom (DoT) to provide Internet Services and a copy of the same has been provided to NIXI. This representation and warranty applies only to ISP members.</p>
    <p><strong>Non-ISP Member:</strong> Warrants and represents that its business of rendering services is conducted legally and validly under the prevailing laws.</p>
    <p><strong>NIXI:</strong> Represents that it is a neutral Internet Exchange facilitating interconnection between ISP/Non-ISP members for efficient routing of domestic traffic and a copy of this representation has been provided to the Member.</p>
    
    <h2>9. Non-compliance</h2>
    <p>In case of violation of any of the provisions of this Agreement including the Payment schedule and technical requirements, NIXI may at its sole discretion, immediately disconnect the Member from Services, terminate the membership, impose financial penalties or take any other action as deemed fit by the NIXI Board.</p>
    
    <h2>10. Fee & Mode of Payment</h2>
    <p>The Member shall be liable to pay the dues by the due dates as per the Routing and Tariff Policy which may be amended from time to time. Non-payment of dues by the due date shall be considered as non-compliance of the terms of this Agreement.</p>
    
    <h2>11. Intellectual Property Rights</h2>
    <p>All logos, trademarks, copyrights and other intellectual property rights shall remain the property of their respective owners. This Agreement does not grant any right to any party to use the intellectual property of the other party.</p>
    
    <h2>12. Insurance and Liability</h2>
    <p>NIXI will take precautions to prevent damage to Member Equipment. However, NIXI will not be liable for any loss or damage, howsoever caused, to the Member Equipment. The Member shall be solely responsible for insuring its own equipment.</p>
    
    <h2>13. TERM and TERMINATION</h2>
    <p>This Agreement shall come into effect from the date of approval by the NIXI Board and shall remain in force until terminated by either party by giving 3 (three) calendar months' written notice to the other party. NIXI reserves the right to disconnect the Member and suspend the Services in case of violation of any terms and conditions of this Agreement.</p>
    
    <h2>14. Severability</h2>
    <p>If any provision of this Agreement is found to be invalid or unenforceable, such provision shall be enforced to the maximum extent permissible, and the remaining provisions of this Agreement shall remain unaffected.</p>
    
    <h2>15. Relationship</h2>
    <p>The parties are independent contractors and neither party has any authority to create any obligation for the other party. This Agreement does not create any partnership, agency or joint venture relationship between the parties.</p>
    
    <h2>16. Force Majeure</h2>
    <p>If the performance of any obligation under this Agreement is prevented, restricted or interfered with by reason of force majeure or any other circumstances beyond the reasonable control of the parties, such occurrence shall not constitute a breach of this Agreement.</p>
    
    <h2>17. Indemnity</h2>
    <p>The parties shall indemnify and hold each other harmless from and against any and all liabilities, demands, losses, costs and expenses (including reasonable attorney's fees) arising out of or relating to any breach of their respective obligations under this Agreement or violation of any existing laws. It is expressly agreed that the officers, employees and personnel of NIXI shall not be personally liable under any circumstances.</p>
    
    <h2>18. Dispute Resolution & Applicable Law</h2>
    <p>Any dispute or difference arising out of or in connection with this Agreement shall be resolved through arbitration in accordance with the Indian Arbitration and Conciliation Act 1996. The arbitration award shall be final and binding on both parties. The venue of arbitration shall be New Delhi and the Delhi Court shall have jurisdiction for any legal proceedings. The cost of arbitration shall be borne equally by both parties. This Agreement shall be governed by and construed in accordance with the laws of India.</p>
    
    <h2>OVERRIDING EFFECT of this Agreement: -</h2>
    <p class="quoted-text">"In case of any repugnance with any other Agreement/Supplementary Agreement, etc., the instant main NIXI Agreement shall prevail and will have superseding effect."</p>
    <p>The authorized signatories of the Parties have set their hands on the date mentioned herein-above.</p>
    
    <div class="signature-section no-break">
        <div class="signature-block clearfix">
            <div class="signature-left">
                <div class="signature-label">For NIXI:</div>
                <div class="signature-line">
                    <div class="signature-field">Signature:</div>
                    <div class="signature-field" style="min-height: 30px;"></div>
                    <div class="signature-field">Name:</div>
                    <div class="signature-field" style="min-height: 15px;"></div>
                    <div class="signature-field">Title:</div>
                    <div class="signature-field" style="min-height: 15px;"></div>
                    <div class="signature-field">Date:</div>
                    <div class="signature-field" style="min-height: 15px;"></div>
                </div>
            </div>
            <div class="signature-right">
                <div class="signature-label">For Member: ISP/CDN, Content Provider, Non-ISP:</div>
                <div class="signature-line">
                    <div class="signature-field">Signature:</div>
                    <div class="signature-field" style="min-height: 30px;"></div>
                    <div class="signature-field">Name:</div>
                    <div class="signature-field" style="min-height: 15px;"></div>
                    <div class="signature-field">Title:</div>
                    <div class="signature-field" style="min-height: 15px;"></div>
                    <div class="signature-field">Date:</div>
                    <div class="signature-field" style="min-height: 15px;"></div>
                </div>
        </div>
        </div>
        </div>
    </div>
</body>
</html>
