// All authoritative state is server-side. This script handles progressive enhancement only.
(function($){'use strict';
  const $doc=$(document);
  $doc.on('submit','form[data-confirm]',function(e){if(!window.confirm(this.dataset.confirm))e.preventDefault();});
  let request=null, searchTimer=null;
  $doc.on('input','[data-live-search] input[name="q"]', function(){
    const input=this, form=input.form, url=new URL(form.action||location.href,location.origin);
    clearTimeout(searchTimer);
    searchTimer=setTimeout(function(){
      const caret=input.selectionStart, query=new URLSearchParams(new FormData(form));
      if(request) request.abort();
      request=$.ajax({url:url.pathname+'?'+query.toString(),dataType:'html'})
      .done(function(html){
        const result=new DOMParser().parseFromString(html,'text/html');
        const found=result.querySelector('.tap-content');
        const target=document.querySelector('.tap-content');
        if(!found||!target) return;
        target.innerHTML=found.innerHTML;
        const next=target.querySelector('[data-live-search] input[name="q"]');
        if(next){next.focus();next.setSelectionRange(caret,caret);}
        history.replaceState(null,'',url.pathname+'?'+query.toString());
      });
    },300);
  });
  function updateRequestFields(){
    const value=$('#request-type').val();
    $('.request-fields').each(function(){
      const enabled=($(this).attr('data-type')||'').split(' ').includes(value);
      $(this).toggle(enabled).find('input,select,textarea').prop('disabled',!enabled);
    });
  }
  $doc.on('change','#request-type',updateRequestFields);updateRequestFields();
  if($.fn.datepicker){$('[data-pms-datepicker]').datepicker({dateFormat:'yy-mm-dd'});}
  $doc.on('submit','form',function(){
    if(this.checkValidity())$(this).find('button[type="submit"]').prop('disabled',true);
  });
  const clock=document.getElementById('clock');
  if(clock){const tick=()=>clock.textContent=new Date().toLocaleString('en-PH',{timeZone:'Asia/Manila'});tick();setInterval(tick,1000);}
  const video=document.getElementById('camera'), file=document.getElementById('photo-input'), preview=document.getElementById('photo-preview');
  let stream=null, photoObjectUrl=null;
  $('#start-camera').on('click',async function(){
    try{
      if(!navigator.mediaDevices)throw new Error('Camera access requires HTTPS or localhost.');
      stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false});
      video.srcObject=stream;video.classList.remove('d-none');preview.classList.add('d-none');
    }catch(err){alert(err.message+' You can upload the photo directly instead.');}
  });
  $('#capture-photo').on('click',function(){
    if(!video||!video.videoWidth)return alert('Start the camera before capturing.');
    const canvas=document.createElement('canvas');canvas.width=640;canvas.height=Math.round(video.videoHeight*640/video.videoWidth);
    canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height);
    canvas.toBlob(function(blob){
      if(!blob)return;
      const dt=new DataTransfer();dt.items.add(new File([blob],'attendance.jpg',{type:'image/jpeg'}));file.files=dt.files;
      if(photoObjectUrl)URL.revokeObjectURL(photoObjectUrl);
      photoObjectUrl=URL.createObjectURL(blob);preview.src=photoObjectUrl;
      preview.classList.remove('d-none');video.classList.add('d-none');
    },'image/jpeg',.8);
  });
  if(file)file.addEventListener('change',function(){
    if(this.files[0]){
      if(photoObjectUrl)URL.revokeObjectURL(photoObjectUrl);
      photoObjectUrl=URL.createObjectURL(this.files[0]);preview.src=photoObjectUrl;preview.classList.remove('d-none');
    }
  });
  $('#scan-nfc').on('click',async function(){
    if(!('NDEFReader' in window))return alert('Web NFC is unavailable. Use a supported Android browser over HTTPS or a USB reader.');
    try{
      const reader=new NDEFReader();await reader.scan();
      reader.addEventListener('reading',e=>{$('#card-uid').val(e.serialNumber.replace(/:/g,'').toUpperCase());});
      this.textContent='Waiting for NFC card…';
    }catch(err){alert(err.message);}
  });
  addEventListener('pagehide',()=>{
    if(stream)stream.getTracks().forEach(t=>t.stop());
    if(photoObjectUrl)URL.revokeObjectURL(photoObjectUrl);
  });
})(jQuery);
