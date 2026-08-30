document.addEventListener('DOMContentLoaded', () => {
 const selector='textarea:not([data-plain-text]):not([rows="1"]):not([name="search"]):not([name="q"])';
 const allowedCommands=new Set(['bold','italic','underline','insertUnorderedList','insertOrderedList','justifyLeft','justifyCenter','justifyRight','undo','redo']);
 const escapeHtml=s=>s.replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 const stripUnsafe=html=>{
   const doc=new DOMParser().parseFromString('<div>'+html+'</div>','text/html'), root=doc.body.firstChild;
   root.querySelectorAll('script,style,iframe,object,embed,form,input,button,svg,math').forEach(n=>n.remove());
   root.querySelectorAll('*').forEach(el=>{
     [...el.attributes].forEach(a=>{
       const n=a.name.toLowerCase(),v=a.value.trim().toLowerCase();
       if(n.startsWith('on')||n==='style'||n==='class'||n==='id'||(n==='href'&&v.startsWith('javascript:'))) el.removeAttribute(a.name);
     });
   });
   return root.innerHTML;
 };
 const button=(label,title,action)=>{const b=document.createElement('button');b.type='button';b.className='erp-rte-btn';b.innerHTML=label;b.title=title;b.addEventListener('click',action);return b;};
 const init=(root=document)=>{
 (root.matches?.(selector)?[root]:[...root.querySelectorAll(selector)]).forEach(textarea=>{
   if(textarea.dataset.richtext==='off'||textarea.closest('.erp-rte')) return;
   const wrap=document.createElement('div');wrap.className='erp-rte';
   const bar=document.createElement('div');bar.className='erp-rte-toolbar';
   const editor=document.createElement('div');editor.className='erp-rte-editor';editor.contentEditable='true';editor.setAttribute('role','textbox');editor.setAttribute('aria-multiline','true');
   const original=textarea.value||'';
   editor.innerHTML=/<[a-z][\s\S]*>/i.test(original)?stripUnsafe(original):escapeHtml(original).replace(/\n/g,'<br>');
   const exec=(cmd,val=null)=>{if(allowedCommands.has(cmd)||cmd==='formatBlock'||cmd==='createLink'){editor.focus();document.execCommand(cmd,false,val);sync();}};
   [['<b>B</b>','Fett','bold'],['<i>I</i>','Kursiv','italic'],['<u>U</u>','Unterstrichen','underline'],['•','Aufzählung','insertUnorderedList'],['1.','Nummerierung','insertOrderedList'],['↶','Rückgängig','undo'],['↷','Wiederholen','redo']].forEach(x=>bar.appendChild(button(x[0],x[1],()=>exec(x[2]))));
   const formats=document.createElement('select');formats.className='erp-rte-format';formats.innerHTML='<option value="p">Normal</option><option value="h2">Überschrift groß</option><option value="h3">Überschrift</option><option value="h4">Überschrift klein</option>';
   formats.addEventListener('change',()=>exec('formatBlock',formats.value));bar.appendChild(formats);
   bar.appendChild(button('🔗','Link',()=>{const u=prompt('Link-Adresse');if(u&&/^https?:\/\//i.test(u))exec('createLink',u);}));
   const find=document.createElement('input');find.type='search';find.className='erp-rte-find';find.placeholder='Im Text suchen…';find.title='Text durchsuchen';
   find.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();const term=find.value.trim();if(!term)return;editor.focus();window.find(term,false,false,true,false,false,false);}});
   bar.appendChild(find);
   const sync=()=>{textarea.value=stripUnsafe(editor.innerHTML);};
   editor.addEventListener('input',sync);editor.addEventListener('blur',sync);
   textarea.parentNode.insertBefore(wrap,textarea);wrap.append(bar,editor,textarea);
   const grip=document.createElement('div');grip.className='erp-rte-resize';grip.title='Textfeld größer/kleiner ziehen';wrap.appendChild(grip);
   let startY=0,startH=0;
   grip.addEventListener('pointerdown',e=>{startY=e.clientY;startH=editor.getBoundingClientRect().height;grip.setPointerCapture(e.pointerId);e.preventDefault();});
   grip.addEventListener('pointermove',e=>{if(!grip.hasPointerCapture(e.pointerId))return;editor.style.height=Math.max(90,Math.min(650,startH+(e.clientY-startY)))+'px';});
   textarea.classList.add('erp-rte-source');textarea.hidden=true;
   textarea.form?.addEventListener('submit',sync);
 });
 };
 window.BetrioInitRichText=init;
 init(document);
});
