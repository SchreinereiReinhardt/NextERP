document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('contactImportSearch')
    const rows = [...document.querySelectorAll('.erp-import-contact')]

    search?.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase()
        rows.forEach(row => {
            row.hidden = query !== '' && !String(row.dataset.search || '').includes(query)
        })
    })

    document.getElementById('selectVisibleContacts')?.addEventListener('click', () => {
        rows.forEach(row => {
            const checkbox = row.querySelector('input[type="checkbox"]')
            if (!row.hidden && checkbox && !checkbox.disabled) {
                checkbox.checked = true
            }
        })
    })
})

;(() => {
    const root = document.getElementById('erpCsvCustomerImport')
    const fileInput = document.getElementById('customerCsvFile')
    if (!root || !fileInput) return
    const mappingArea = document.getElementById('csvMappingArea')
    const mappingRows = document.getElementById('csvMappingRows')
    const previewArea = document.getElementById('csvPreviewArea')
    const status = document.getElementById('csvImportStatus')
    let headers = [], sample = []
    const fields = {
        '':'Nicht importieren','customer_no':'Kundennummer','customer_type':'Kundentyp','name':'Firma / Kundenname','salutation':'Anrede','title':'Titel','first_name':'Vorname','last_name':'Nachname','address_addition':'Adresszusatz / c/o','contact_name':'Ansprechpartner','street':'Straße','postal_code':'PLZ','city':'Ort','country':'Land','phone':'Telefon','mobile':'Mobil','email':'E-Mail','invoice_email':'Rechnungs-E-Mail','vat_id':'USt-IdNr.','tax_no':'Steuernummer','leitweg_id':'Leitweg-ID','supplier_no':'Lieferantennummer','buyer_reference':'Käuferreferenz','purchase_order_reference':'Bestellreferenz','cost_center':'Kostenstelle','invoice_format':'Rechnungsformat','datev_debtor_account':'DATEV-Debitorenkonto','notes':'Notizen'
    }
    const aliases = {
        customer_no:['kundennummer','kunden nr','kundennr','kdnr','debitor','debitorennummer','customer no','customer number'],customer_type:['kundentyp','kundenart','typ','type'],name:['firma','firmenname','unternehmen','company','organisation','organization','kundenname'],salutation:['anrede','salutation'],title:['titel','title'],first_name:['vorname','first name','firstname','given name'],last_name:['nachname','name','surname','last name','lastname'],address_addition:['adresszusatz','adresszusatz 1','adresszusatz 2','zusatz','c/o','co','address 2','address2'],contact_name:['ansprechpartner','kontaktperson','contact person'],street:['straße','strasse','street','anschrift'],postal_code:['plz','postleitzahl','zip','postal code'],city:['ort','stadt','city'],country:['land','country'],phone:['telefon','tel','phone'],mobile:['mobil','handy','mobile'],email:['e-mail','email','mail'],invoice_email:['rechnungs-e-mail','rechnung email','invoice email','billing email'],vat_id:['ust-idnr','ust id','ust-id','umsatzsteuer id','vat id','vatid'],tax_no:['steuernummer','steuer nr','tax no','tax number'],leitweg_id:['leitweg-id','leitweg id'],supplier_no:['lieferantennummer','lieferanten nr'],buyer_reference:['käuferreferenz','buyer reference'],purchase_order_reference:['bestellreferenz','bestellnummer','purchase order'],cost_center:['kostenstelle','cost center'],invoice_format:['rechnungsformat','invoice format'],datev_debtor_account:['datev debitorenkonto','debitorenkonto','datev konto'],notes:['notiz','notizen','bemerkung','bemerkungen','notes']
    }
    const norm = v => String(v||'').trim().toLowerCase().replace(/[._-]+/g,' ').replace(/\s+/g,' ')
    const guess = h => { const n=norm(h); for (const [f,list] of Object.entries(aliases)) if (list.includes(n)) return f; return '' }
    const parseLine = (line, delim) => { const out=[]; let cur='', q=false; for(let i=0;i<line.length;i++){const c=line[i];if(c==='"'){if(q&&line[i+1]==='"'){cur+='"';i++}else q=!q}else if(c===delim&&!q){out.push(cur);cur=''}else cur+=c}out.push(cur);return out }
    const detectDelimiter = line => [';',',','\t'].sort((a,b)=>(line.split(b).length-line.split(a).length))[0]
    const showStatus = (msg, bad=false) => { status.hidden=false; status.textContent=msg; status.classList.toggle('erp-error',bad) }
    const currentMapping = () => { const m={}; mappingRows.querySelectorAll('select').forEach(sel=>{if(sel.value)m[sel.dataset.header]=sel.value}); return m }
    fileInput.addEventListener('change', async () => {
        previewArea.hidden=true; mappingArea.hidden=true
        const file=fileInput.files?.[0]; if(!file)return
        const text=await file.text(); const lines=text.replace(/^\uFEFF/,'').split(/\r?\n/).filter(x=>x.trim()!==''); if(lines.length<2){showStatus('Die CSV enthält keine Datensätze.',true);return}
        const delim=detectDelimiter(lines[0]); headers=parseLine(lines[0],delim).map(x=>x.trim()); sample=parseLine(lines[1],delim)
        mappingRows.innerHTML=''
        headers.forEach((h,i)=>{const tr=document.createElement('tr'), td1=document.createElement('td'),td2=document.createElement('td'),td3=document.createElement('td'),sel=document.createElement('select');td1.textContent=h;td2.textContent=sample[i]||'';sel.dataset.header=h;Object.entries(fields).forEach(([v,l])=>{const o=document.createElement('option');o.value=v;o.textContent=l;sel.appendChild(o)});sel.value=guess(h);td3.appendChild(sel);tr.append(td1,td2,td3);mappingRows.appendChild(tr)})
        mappingArea.hidden=false; status.hidden=true
    })
    async function send(url){const file=fileInput.files?.[0];if(!file)throw new Error('Bitte eine CSV-Datei auswählen.');const fd=new FormData();fd.append('csv',file);fd.append('mapping',JSON.stringify(currentMapping()));fd.append('requesttoken',document.getElementById('csvRequestToken').value);const r=await fetch(url,{method:'POST',body:fd,credentials:'same-origin'});const data=await r.json();if(!r.ok||!data.ok)throw new Error(data.message||'Import fehlgeschlagen.');return data}
    document.getElementById('csvAnalyzeButton')?.addEventListener('click',async()=>{try{showStatus('CSV wird geprüft …');const d=await send(root.dataset.analyzeUrl);document.getElementById('csvTotal').textContent=d.total;document.getElementById('csvNew').textContent=d.new;document.getElementById('csvDuplicates').textContent=d.duplicates;document.getElementById('csvInvalid').textContent=d.invalid;const box=document.getElementById('csvPreviewRows');box.innerHTML='';d.preview.forEach(r=>{const div=document.createElement('div');div.className='erp-notice';div.textContent=`Zeile ${r.row}: ${r.name||'—'} · ${r.message}`;box.appendChild(div)});previewArea.hidden=false;status.hidden=true}catch(e){showStatus(e.message,true)}})
    document.getElementById('csvImportButton')?.addEventListener('click',async()=>{try{showStatus('Kunden werden importiert …');const d=await send(root.dataset.importUrl);showStatus(d.message);previewArea.hidden=true;setTimeout(()=>{window.location.href=OC.generateUrl('/apps/reinhardterp/customers')+'?message='+encodeURIComponent(d.message)},700)}catch(e){showStatus(e.message,true)}})
})()
