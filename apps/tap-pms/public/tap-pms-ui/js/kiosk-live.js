/* TAP original kiosk presentation + secure server-backed attendance. No PMSStore or browser payroll data. */
(()=>{'use strict';
const el=id=>document.getElementById(id), ready=document.querySelector('meta[name="kiosk-authorized"]')?.content==='yes';
const scan=el('kioskScanBuffer'), video=el('camPreview'), canvas=el('camCanvas'), status=el('camStatus'), error=el('kioskError');
const overlay=el('kioskSuccessCard');
let cam=null, busy=false, clearOverlay=null;
const clock=()=>{const now=new Date();
  el('liveClockDisplay').textContent=now.toLocaleTimeString('en-US',{timeZone:'Asia/Manila'});
  el('liveDateDisplay').textContent=now.toLocaleDateString('en-US',{timeZone:'Asia/Manila',weekday:'long',year:'numeric',month:'long',day:'numeric'});
};clock();setInterval(clock,1000);
function showError(msg){const str=String(msg||'Unable to record attendance.');error.textContent=str;error.classList.remove('d-none');
 const modal=el('kioskNoticeModal');if(modal&&window.bootstrap){el('kioskNoticeMessage').textContent=str;window.bootstrap.Modal.getOrCreateInstance(modal).show();}}
function clearError(){error.classList.add('d-none');error.textContent='';}
async function startCamera(){try{
 if(cam)return true;
 if(!navigator.mediaDevices?.getUserMedia)throw new Error('Camera requires HTTPS or localhost.');
 cam=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:640},height:{ideal:480}},audio:false});
 video.srcObject=cam;await video.play();status.textContent='Camera on';status.style.color='#5fd0a0';return true;
 }catch(e){status.textContent='Camera unavailable: '+e.message;status.style.color='#ff8a8a';return false;}}
async function takePhoto(){if(!cam||!video.videoWidth){if(!(await startCamera()))return null;
  if(!video.videoWidth)await new Promise(resolve=>video.addEventListener('loadeddata',resolve,{once:true}));}
 if(!video.videoWidth)return null;
 canvas.width=480;canvas.height=Math.round(480*video.videoHeight/video.videoWidth);
 canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);
 return new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',.72));}
function hideSuccess(){overlay?.classList.add('d-none');el('idleDisplay')?.classList.remove('d-none');scan.value='';scan.focus();}
function showSuccess(row,photo){
 const isIn=row.event_type.endsWith('_in'), time=row.display_time||'';
 const name=row.employee_name||row.employee_no;
 el('ksName').textContent=name;el('ksMeta').textContent=[row.employee_no,row.position].filter(Boolean).join(' · ');
 el('ksAction').textContent=row.event_type.replace('_',' ').toUpperCase();el('ksTime').textContent=time;
 el('ksStatus').textContent=row.status_label||'Recorded';el('ksStatus').className='ks-pill '+(/Late/i.test(row.status_label||'')?'ks-late':/Early/i.test(row.status_label||'')?'ks-warn':'ks-ok');
 el('ksDate').textContent=new Date(row.occurred_at).toLocaleDateString('en-US',{timeZone:'Asia/Manila',dateStyle:'full'});
 el('ksSync').textContent='Saved on server';el('ksSync').className='ks-sync ks-saved';
 el('ksPhoto').src=URL.createObjectURL(photo);
 overlay.className='ks-overlay '+(isIn?'ks-in':'ks-out');
 if(clearOverlay)clearTimeout(clearOverlay);clearOverlay=setTimeout(hideSuccess,3500);
}
async function punch(uid){
 if(busy)return;
 if(!ready){showError('Ask an administrator to activate this browser once before scanning.');return;}
 if(!uid?.trim())return;
 busy=true;clearError();el('btnManualSubmit').disabled=true;
 try{
  const photo=await takePhoto();if(!photo){showError('A live camera photo is required. Enable the camera and try again.');return;}
  const data=new FormData();data.append('card_uid',uid.trim());
  data.append('client_key',crypto.randomUUID());data.append('photo',photo,'attendance.jpg');
  const res=await fetch('/kiosk/punch',{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:data});
  const result=await res.json().catch(()=>({}));
  if(!res.ok)throw new Error(Object.values(result.errors||{})[0]?.[0]||result.message||('Attendance service error '+res.status));
  showSuccess(result.data,photo);
 }catch(e){showError(e.message||'Attendance not saved. Try again.');}
 finally{busy=false;el('btnManualSubmit').disabled=false;scan.value='';scan.focus();}
}
scan?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();punch(scan.value);}});
el('btnManualSubmit')?.addEventListener('click',()=>punch(scan.value));
el('btnEnableNfc')?.addEventListener('click',async()=>{
 await startCamera();const s=el('nfcLiveStatus');
 if(!('NDEFReader' in window)){s.textContent='Web NFC is unavailable. Scan with a USB reader or enter card UID.';return;}
 try{const reader=new NDEFReader();await reader.scan();s.textContent='Reader on';s.style.color='#5fd0a0';
 reader.onreading=e=>e.serialNumber&&punch(e.serialNumber);
 reader.onreadingerror=()=>s.textContent='Card unreadable. Please try again.';
 }catch(e){s.textContent='Reader error: '+e.message;}
});
overlay?.addEventListener('click',hideSuccess);
addEventListener('pagehide',()=>{if(cam)cam.getTracks().forEach(t=>t.stop());});
// A camera permission is attempted on load; browsers that require user gesture use Enable reader.
if(ready)startCamera();
})();
