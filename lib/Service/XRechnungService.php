<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

final class XRechnungService {
    private const CUSTOMIZATION_ID = 'urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0';
    private const PROFILE_ID = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';

    public function create(array $invoice, array $customer, array $items, array $company): string {
        $this->validate($invoice, $customer, $items, $company);

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:Invoice-2', 'Invoice');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $doc->appendChild($root);

        $warnings = [];
        if (trim((string)($company['vatId'] ?? '')) === '' && trim((string)($company['taxNo'] ?? '')) === '') {
            $warnings[] = 'Eigene USt-IdNr. oder Steuernummer fehlt';
        }
        $customerStreet = trim((string)($customer['street'] ?? ''));
        $customerZip = trim((string)($customer['postal_code'] ?? ($customer['zip'] ?? '')));
        $customerCity = trim((string)($customer['city'] ?? ''));
        if ($customerStreet === '' || $customerZip === '' || $customerCity === '') {
            $warnings[] = 'Kundenanschrift ist unvollstaendig';
        }
        $customerRef = trim((string)($customer['leitweg_id'] ?? ''));
        if ($customerRef === '') $customerRef = trim((string)($customer['buyer_reference'] ?? ''));
        if ($customerRef === '') $customerRef = trim((string)($customer['customer_no'] ?? ''));
        if ($customerRef === '') {
            $warnings[] = 'Kaeuferreferenz / Buyer Reference fehlt';
        }
        if ($warnings) {
            $root->appendChild($doc->createComment(
                ' BETRIO ENTWICKLUNGSHINWEIS: Diese XRechnung ist noch nicht vollstaendig validierbar. Fehlende Angaben: '.implode('; ', $warnings).' '
            ));
        }

        $this->cbc($doc,$root,'CustomizationID',self::CUSTOMIZATION_ID);
        $this->cbc($doc,$root,'ProfileID',self::PROFILE_ID);
        $this->cbc($doc,$root,'ID',(string)$invoice['invoice_no']);
        $this->cbc($doc,$root,'IssueDate',(string)$invoice['invoice_date']);
        if (!empty($invoice['due_date'])) $this->cbc($doc,$root,'DueDate',(string)$invoice['due_date']);
        $this->cbc($doc,$root,'InvoiceTypeCode','380');
        $this->cbc($doc,$root,'DocumentCurrencyCode','EUR');

        $buyerReference = trim((string)($customer['leitweg_id'] ?? ''));
        if ($buyerReference === '') $buyerReference = trim((string)($customer['buyer_reference'] ?? ''));
        if ($buyerReference === '') $buyerReference = trim((string)($customer['customer_no'] ?? ''));
        if ($buyerReference === '') $buyerReference = '-';
        $this->cbc($doc,$root,'BuyerReference',$buyerReference);

        if (!empty($customer['purchase_order_reference'])) {
            $order = $this->cac($doc,$root,'OrderReference');
            $this->cbc($doc,$order,'ID',(string)$customer['purchase_order_reference']);
        }

        $seller = $this->cac($doc,$root,'AccountingSupplierParty');
        $sellerParty = $this->cac($doc,$seller,'Party');
        $sellerEmail = trim((string)($company['email'] ?? ''));
        if ($sellerEmail !== '') {
            $sellerEndpoint = $this->cbc($doc,$sellerParty,'EndpointID',$sellerEmail);
            $sellerEndpoint->setAttribute('schemeID','EM');
        }
        $sellerName = $this->cac($doc,$sellerParty,'PartyName');
        $this->cbc($doc,$sellerName,'Name',(string)$company['name']);
        $this->address($doc,$sellerParty,$company);
        $sellerVat = trim((string)($company['vatId'] ?? ''));
        $sellerTaxNo = trim((string)($company['taxNo'] ?? ''));
        if ($sellerVat !== '' || $sellerTaxNo !== '') {
            $sellerTax = $this->cac($doc,$sellerParty,'PartyTaxScheme');
            $this->cbc($doc,$sellerTax,'CompanyID',$sellerVat !== '' ? $sellerVat : $sellerTaxNo);
            $taxScheme = $this->cac($doc,$sellerTax,'TaxScheme');
            $this->cbc($doc,$taxScheme,'ID',$sellerVat !== '' ? 'VAT' : 'FC');
        }
        $sellerLegal = $this->cac($doc,$sellerParty,'PartyLegalEntity');
        $this->cbc($doc,$sellerLegal,'RegistrationName',(string)$company['name']);

        // BR-DE-2 / BG-6: seller contact is mandatory for German XRechnung.
        $contactName = trim((string)($company['owner'] ?? ''));
        if ($contactName === '') $contactName = trim((string)($company['name'] ?? ''));
        $contactPhone = trim((string)($company['phone'] ?? ''));
        if ($contactPhone === '') $contactPhone = trim((string)($company['mobile'] ?? ''));
        if ($contactName !== '' || $contactPhone !== '' || $sellerEmail !== '') {
            $contact = $this->cac($doc,$sellerParty,'Contact');
            if ($contactName !== '') $this->cbc($doc,$contact,'Name',$contactName);
            if ($contactPhone !== '') $this->cbc($doc,$contact,'Telephone',$contactPhone);
            if ($sellerEmail !== '') $this->cbc($doc,$contact,'ElectronicMail',$sellerEmail);
        }

        $buyer = $this->cac($doc,$root,'AccountingCustomerParty');
        $buyerParty = $this->cac($doc,$buyer,'Party');
        $buyerMail = trim((string)($customer['invoice_email'] ?? ''));
        if ($buyerMail === '') $buyerMail = trim((string)($customer['email'] ?? ''));
        if ($buyerMail !== '') {
            $buyerEndpoint = $this->cbc($doc,$buyerParty,'EndpointID',$buyerMail);
            $buyerEndpoint->setAttribute('schemeID','EM');
        }
        $buyerName = $this->cac($doc,$buyerParty,'PartyName');
        $this->cbc($doc,$buyerName,'Name',(string)$customer['name']);
        $this->address($doc,$buyerParty,$customer);
        if (!empty($customer['vat_id'])) {
            $buyerTax = $this->cac($doc,$buyerParty,'PartyTaxScheme');
            $this->cbc($doc,$buyerTax,'CompanyID',(string)$customer['vat_id']);
            $buyerTaxScheme = $this->cac($doc,$buyerTax,'TaxScheme');
            $this->cbc($doc,$buyerTaxScheme,'ID','VAT');
        }
        $buyerLegal = $this->cac($doc,$buyerParty,'PartyLegalEntity');
        $this->cbc($doc,$buyerLegal,'RegistrationName',(string)$customer['name']);

        if (!empty($invoice['service_date'])) {
            $delivery = $this->cac($doc,$root,'Delivery');
            $this->cbc($doc,$delivery,'ActualDeliveryDate',(string)$invoice['service_date']);
        }

        $iban = preg_replace('/\s+/', '', (string)($company['bank1_iban'] ?? ''));
        if ($iban !== '') {
            $payment = $this->cac($doc,$root,'PaymentMeans');
            $this->cbc($doc,$payment,'PaymentMeansCode','58');
            $account = $this->cac($doc,$payment,'PayeeFinancialAccount');
            $this->cbc($doc,$account,'ID',$iban);
            if (!empty($company['bank1_name'])) $this->cbc($doc,$account,'Name',(string)$company['bank1_name']);
        }

        if (!empty($invoice['outro_text']) || !empty($invoice['notes'])) {
            $terms = $this->cac($doc,$root,'PaymentTerms');
            $note = trim(strip_tags((string)($invoice['outro_text'] ?? '')));
            if ($note === '') $note = trim(strip_tags((string)($invoice['notes'] ?? '')));
            if ($note !== '') $this->cbc($doc,$terms,'Note',$note);
        }

        $vatRate = (float)($invoice['vat_rate'] ?? 19);
        $taxMode=(string)($invoice['tax_mode']??'standard19');
        $taxCategory=$taxMode==='reverse_charge_13b'?'AE':($taxMode==='small_business'?'E':($vatRate>0?'S':'Z'));
        $taxReason=$taxMode==='reverse_charge_13b'?'Steuerschuldnerschaft des Leistungsempfängers':($taxMode==='small_business'?'Steuerbefreiung für Kleinunternehmer gemäß § 19 UStG':'');
        $net = round((float)$invoice['net_amount'],2);
        $gross = round((float)$invoice['gross_amount'],2);
        $taxAmount = round($gross-$net,2);

        $taxTotal = $this->cac($doc,$root,'TaxTotal');
        $tax = $this->cbc($doc,$taxTotal,'TaxAmount',$this->money($taxAmount));
        $tax->setAttribute('currencyID','EUR');
        $sub = $this->cac($doc,$taxTotal,'TaxSubtotal');
        $taxable = $this->cbc($doc,$sub,'TaxableAmount',$this->money($net)); $taxable->setAttribute('currencyID','EUR');
        $subTax = $this->cbc($doc,$sub,'TaxAmount',$this->money($taxAmount)); $subTax->setAttribute('currencyID','EUR');
        $cat = $this->cac($doc,$sub,'TaxCategory');
        $this->cbc($doc,$cat,'ID',$taxCategory);
        $this->cbc($doc,$cat,'Percent',$this->decimal($vatRate));
        if($taxReason!=='')$this->cbc($doc,$cat,'TaxExemptionReason',$taxReason);
        $scheme = $this->cac($doc,$cat,'TaxScheme'); $this->cbc($doc,$scheme,'ID','VAT');

        $legal = $this->cac($doc,$root,'LegalMonetaryTotal');
        foreach (['LineExtensionAmount'=>$net,'TaxExclusiveAmount'=>$net,'TaxInclusiveAmount'=>$gross,'PayableAmount'=>$gross] as $tag=>$value) {
            $n=$this->cbc($doc,$legal,$tag,$this->money($value)); $n->setAttribute('currencyID','EUR');
        }

        $pos=0;
        foreach ($items as $item) {
            if (!empty($item['is_alternative'])) continue;
            $pos++;
            $line = $this->cac($doc,$root,'InvoiceLine');
            $this->cbc($doc,$line,'ID',(string)$pos);
            $qty = $this->cbc($doc,$line,'InvoicedQuantity',$this->decimal((float)$item['quantity']));
            $qty->setAttribute('unitCode',$this->unitCode((string)($item['unit'] ?? 'Stk.')));
            $amount = $this->cbc($doc,$line,'LineExtensionAmount',$this->money((float)$item['total_price']));
            $amount->setAttribute('currencyID','EUR');
            $product = $this->cac($doc,$line,'Item');
            $this->cbc($doc,$product,'Name',$this->plain((string)$item['description']));
            $classified = $this->cac($doc,$product,'ClassifiedTaxCategory');
            $this->cbc($doc,$classified,'ID',$taxCategory);
            $this->cbc($doc,$classified,'Percent',$this->decimal($vatRate));
            if($taxReason!=='')$this->cbc($doc,$classified,'TaxExemptionReason',$taxReason);
            $lineTax = $this->cac($doc,$classified,'TaxScheme'); $this->cbc($doc,$lineTax,'ID','VAT');
            $price = $this->cac($doc,$line,'Price');
            $priceAmount = $this->cbc($doc,$price,'PriceAmount',$this->money((float)$item['unit_price']));
            $priceAmount->setAttribute('currencyID','EUR');
        }

        return $doc->saveXML() ?: '';
    }

    public function warnings(array $invoice, array $customer, array $items, array $company): array {
        $errors=[];
        if (($invoice['status'] ?? '') === 'draft') $errors[]='Die Rechnung muss zuerst finalisiert werden.';
        foreach (['invoice_no'=>'Rechnungsnummer','invoice_date'=>'Rechnungsdatum'] as $k=>$label) if (trim((string)($invoice[$k]??''))==='') $errors[]=$label.' fehlt.';
        foreach (['name'=>'Firmenname','street'=>'Straße','zip'=>'PLZ','city'=>'Ort','email'=>'E-Mail'] as $k=>$label) if (trim((string)($company[$k]??''))==='') $errors[]='Eigene Firmendaten: '.$label.' fehlt.';
        if (trim((string)($company['vatId']??''))==='' && trim((string)($company['taxNo']??''))==='') $errors[]='Eigene Firmendaten: USt-IdNr. oder Steuernummer fehlt.';
        $sellerContactName=trim((string)($company['owner']??''));if($sellerContactName==='')$sellerContactName=trim((string)($company['name']??''));
        $sellerContactPhone=trim((string)($company['phone']??''));if($sellerContactPhone==='')$sellerContactPhone=trim((string)($company['mobile']??''));
        if($sellerContactName==='')$errors[]='Eigene Firmendaten: Ansprechpartner fehlt.';
        if($sellerContactPhone==='')$errors[]='Eigene Firmendaten: Telefon für XRechnung fehlt.';
        if (trim((string)($customer['name']??''))==='') $errors[]='Kunde: Name fehlt.';
        $street=trim((string)($customer['street']??''));$zip=trim((string)($customer['postal_code']??($customer['zip']??'')));$city=trim((string)($customer['city']??''));
        if($street===''||$zip===''||$city==='')$errors[]='Kunde: vollständige Rechnungsanschrift fehlt.';
        $mail=trim((string)($customer['invoice_email']??''));if($mail==='')$mail=trim((string)($customer['email']??''));
        if($mail===''||!filter_var($mail,FILTER_VALIDATE_EMAIL))$errors[]='Kunde: gültige Rechnungs-E-Mail fehlt.';
        $ref=trim((string)($customer['leitweg_id']??''));if($ref==='')$ref=trim((string)($customer['buyer_reference']??''));if($ref==='')$ref=trim((string)($customer['customer_no']??''));
        if($ref==='')$errors[]='Kunde: Käuferreferenz / Buyer Reference fehlt.';
        if(($customer['customer_type']??'business')==='public'&&trim((string)($customer['leitweg_id']??''))==='')$errors[]='Öffentlicher Auftraggeber: Leitweg-ID fehlt.';
        if(!$items)$errors[]='Rechnungspositionen fehlen.';
        return $errors;
    }

    public function validate(array $invoice, array $customer, array $items, array $company): void {
        $errors = $this->warnings($invoice, $customer, $items, $company);
        if ($errors !== []) {
            throw new \InvalidArgumentException('XRechnung kann nicht erzeugt werden: '.implode(' ', $errors));
        }
    }

    private function address(\DOMDocument $doc,\DOMElement $party,array $data):void {
        $a=$this->cac($doc,$party,'PostalAddress');
        $street=(string)($data['street']??'');
        $zip=(string)($data['postal_code']??($data['zip']??''));
        $city=(string)($data['city']??'');
        $country=(string)($data['country']??'DE');
        if (trim($street) !== '') $this->cbc($doc,$a,'StreetName',$street);
        if (trim($city) !== '') $this->cbc($doc,$a,'CityName',$city);
        if (trim($zip) !== '') $this->cbc($doc,$a,'PostalZone',$zip);
        if (trim($country) !== '') {
            $c=$this->cac($doc,$a,'Country');
            $this->cbc($doc,$c,'IdentificationCode',$this->countryCode($country));
        }
    }
    private function countryCode(string $value):string {
        $v=mb_strtolower(trim($value));
        return match($v){'','de','deutschland','germany'=>'DE','at','österreich','austria'=>'AT','ch','schweiz','switzerland'=>'CH',default=>strtoupper(substr(trim($value),0,2))};
    }
    private function unitCode(string $unit):string {
        $u=mb_strtolower(trim($unit));
        return match($u){'std','h','stunde','stunden'=>'HUR','m','meter'=>'MTR','m²','m2','qm'=>'MTK','m³','m3'=>'MTQ','kg'=>'KGM','l','liter'=>'LTR',default=>'C62'};
    }
    private function plain(string $v):string {
        $v=preg_replace('#<br\\s*/?>#i',"\n",$v)??$v;
        return trim(html_entity_decode(strip_tags($v),ENT_QUOTES|ENT_HTML5,'UTF-8'));
    }
    private function money(float $v):string{return number_format($v,2,'.','');}
    private function decimal(float $v):string{$s=rtrim(rtrim(number_format($v,4,'.',''),'0'),'.');return $s===''?'0':$s;}
    private function cac(\DOMDocument $d,\DOMElement $p,string $n):\DOMElement{$e=$d->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2','cac:'.$n);$p->appendChild($e);return $e;}
    private function cbc(\DOMDocument $d,\DOMElement $p,string $n,string $v):\DOMElement{$e=$d->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2','cbc:'.$n);$e->appendChild($d->createTextNode($v));$p->appendChild($e);return $e;}
}
