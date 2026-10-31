let validatedMeeting = null;
function invalidatePreview(){validatedMeeting=null;$('#meeting-preview').prop('hidden',true);}
function editor(record,day,start){
 invalidatePreview();
 const form=$('#meeting-editor');form.attr('action',record.id?'/academic/schedules/'+record.id:'/academic/schedules');form.data('meeting-id',record.id||'');$('#meeting-form-title').text(record.id?'Edit scheduled meeting':'Create meeting');
 $('#offering_id').val(record.offering_id).trigger('change');if(record.professor_id)$('#professor_id').val(record.professor_id).trigger('change');if(record.room_id)$('#room_id').val(record.room_id);
 $('#day').val(day||record.day||'Mon');$('#start_time').val(start||(record.start_time||'').slice(0,5));
 if(record.end_time){let end=record.end_time.slice(0,5);if(start){const minutes=t=>Number(t.slice(0,2))*60+Number(t.slice(3,5));const total=minutes(start)+minutes(record.end_time)-minutes(record.start_time);end=String(Math.floor(total/60)).padStart(2,'0')+':'+String(total%60).padStart(2,'0');}$('#end_time').val(end);}
 updateProfessorPanel();document.getElementById('schedule-form').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});$('#end_time').trigger('focus');
}
$('.draggable-offering').on('dragstart',function(e){e.originalEvent.dataTransfer.setData('text/plain',JSON.stringify({offering_id:$(this).data('id')}));}).on('click',function(){editor({offering_id:$(this).data('id')});});
$('.schedule-event').on('dragstart',function(e){e.originalEvent.dataTransfer.setData('text/plain',JSON.stringify($(this).data('record')));});
$('.edit-meeting').on('click',function(){editor($(this).closest('.schedule-event').data('record'));});
$('.schedule-slot').on('dragover',function(e){e.preventDefault();$(this).addClass('drag-over');}).on('dragleave',function(){$(this).removeClass('drag-over');}).on('drop',function(e){e.preventDefault();$(this).removeClass('drag-over');try{editor(JSON.parse(e.originalEvent.dataTransfer.getData('text/plain')),$(this).data('day'),$(this).data('time'));}catch{}});
$('#reset-meeting').on('click',function(){document.getElementById('meeting-editor').reset();$('#meeting-editor').attr('action','/academic/schedules').data('meeting-id','');$('#meeting-form-title').text('Create meeting');$('#schedule-result').empty();invalidatePreview();$('#offering_id').trigger('change');});
$('#period-filter,#program-filter,#year-filter,#section-filter,#floor-filter,#room-filter,#day-filter').on('change',function(){
 const matches=(el,field,value)=>!value||String($(el).data(field))===String(value);
 $('.draggable-offering,.schedule-event').each(function(){let visible=Boolean($('#period-filter').val())&&matches(this,'period',$('#period-filter').val())&&matches(this,'program',$('#program-filter').val())&&matches(this,'year',$('#year-filter').val())&&matches(this,'section',$('#section-filter').val());if($(this).hasClass('schedule-event'))visible=visible&&matches(this,'floor',$('#floor-filter').val())&&matches(this,'room',$('#room-filter').val());$(this).toggle(visible);});
 $('.day-column').each(function(){$(this).toggle(matches(this,'day',$('#day-filter').val()));});
}).trigger('change');
$('#academic-year-filter,#semester-filter').on('change',function(){
 $('#period-filter option').each(function(){const match=(!$('#academic-year-filter').val()||String($(this).data('academic-year'))===$('#academic-year-filter').val())&&(!$('#semester-filter').val()||String($(this).data('semester'))===$('#semester-filter').val());$(this).prop('disabled',!match).prop('hidden',!match);});
 if(!$('#period-filter option:selected').length||$('#period-filter option:selected').prop('disabled'))$('#period-filter').val($('#period-filter option:not(:disabled)').first().val()||'');$('#period-filter').trigger('change');
});
function scheduleError(xhr){const errors=xhr.responseJSON?.errors;const alert=$('<div class="alert alert-danger" role="alert">');if(errors)Object.values(errors).flat().forEach(message=>alert.append($('<div>').text(message)));else alert.text('The schedule could not be validated or saved. Refresh and try again.');$('#schedule-result').empty().append(alert);}
$('#meeting-editor').on('input change','input,select',invalidatePreview);
$('#meeting-editor').on('submit',function(e){
 e.preventDefault();invalidatePreview();const form=$(this),button=form.find('[type="submit"]');button.prop('disabled',true);$('#schedule-result').empty();
 const payload=form.serialize();const url=form.attr('action'),id=form.data('meeting-id');
 $.ajax({url:'/scheduler/preview',method:'POST',data:payload+(id?'&id='+encodeURIComponent(id):''),headers:{Accept:'application/json'}}).done(function(result){
  if(payload!==form.serialize()||url!==form.attr('action'))return;
  validatedMeeting={payload,url};$('#preview-summary').text($('#offering_id option:selected').text()+' · '+$('#professor_id option:selected').text()+' · '+$('#room_id option:selected').text()+' · '+$('#day').val()+' '+$('#start_time').val()+'–'+$('#end_time').val());$('#preview-validation').text(result.message);$('#meeting-preview').prop('hidden',false);$('#confirm-meeting').trigger('focus');
 }).fail(scheduleError).always(function(){button.prop('disabled',false);});
});
$('#confirm-meeting').on('click',function(){
 const form=$('#meeting-editor');if(!validatedMeeting||validatedMeeting.payload!==form.serialize()||validatedMeeting.url!==form.attr('action')){invalidatePreview();return;}
 const button=$(this);button.prop('disabled',true);form.find('input,select,button').prop('disabled',true);
 $.ajax({url:validatedMeeting.url,method:'POST',data:validatedMeeting.payload,headers:{Accept:'application/json'}}).done(function(){window.location.href='/scheduler';}).fail(function(xhr){form.find('input,select,button').prop('disabled',false);invalidatePreview();scheduleError(xhr);$('#offering_id').trigger('change');}).always(function(){button.prop('disabled',false);});
});
function updateProfessorPanel(){
 if(typeof teachingQualifications==='undefined')return;
 const subject=Number($('#offering_id option:selected').data('subject')),day=$('#day').val(),start=$('#start_time').val(),end=$('#end_time').val();const id=Number($('#meeting-editor').data('meeting-id'));
 const period=String($('.draggable-offering').filter(function(){return String($(this).data('id'))===String($('#offering_id').val());}).first().data('period'));
 const panel=$('#available-professors').empty();let matches=0;
 professorRecords.filter(p=>teachingQualifications.some(q=>Number(q.professor_id)===Number(p.id)&&Number(q.subject_id)===subject)).forEach(p=>{
  const windows=professorAvailability.filter(w=>Number(w.professor_id)===Number(p.id)&&w.day===day);
  const covered=start&&end&&end>start&&windows.some(w=>w.start_time.slice(0,5)<=start&&w.end_time.slice(0,5)>=end);
  const conflict=start&&end&&scheduledMeetings.some(m=>Number(m.id)!==id&&Number(m.professor_id)===Number(p.id)&&String(m.period_id)===period&&m.day===day&&m.start_time.slice(0,5)<end&&m.end_time.slice(0,5)>start);
  const row=$('<div class="border-bottom py-2 small">');row.append($('<strong>').text(p.name));row.append($('<div>').text(p.department+' · '+p.employment_type.replaceAll('_',' ')));row.append($('<div>').text(windows.map(w=>w.start_time.slice(0,5)+'–'+w.end_time.slice(0,5)).join(', ')||'No availability on '+day));
  row.append($('<div class="text-secondary">').text(!start||!end?'Set meeting times to check availability':conflict?'Another class overlaps':covered?'Available for this meeting':'Outside recorded hours'));panel.append(row);matches++;
 });if(!matches)panel.text('No authorized professors for the selected subject.');
}
$('#offering_id,#professor_id,#day,#start_time,#end_time').on('change input',updateProfessorPanel);
$(updateProfessorPanel);
