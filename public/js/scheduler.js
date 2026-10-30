
function editor(record,day,start){
 const form=$('#meeting-editor');form.attr('action',record.id?'/academic/schedules/'+record.id:'/academic/schedules');$('#meeting-form-title').text(record.id?'Edit scheduled meeting':'Create meeting');
 $('#offering_id').val(record.offering_id);if(record.professor_id)$('#professor_id').val(record.professor_id);if(record.room_id)$('#room_id').val(record.room_id);
 $('#day').val(day||record.day||'Mon');$('#start_time').val(start||(record.start_time||'').slice(0,5));
 if(record.end_time){let end=record.end_time.slice(0,5);if(start){const minutes=t=>Number(t.slice(0,2))*60+Number(t.slice(3,5));const total=minutes(start)+minutes(record.end_time)-minutes(record.start_time);end=String(Math.floor(total/60)).padStart(2,'0')+':'+String(total%60).padStart(2,'0');}$('#end_time').val(end);}
 document.getElementById('schedule-form').scrollIntoView({behavior:'smooth'});$('#end_time').trigger('focus');
}
$('.draggable-offering').on('dragstart',function(e){e.originalEvent.dataTransfer.setData('text/plain',JSON.stringify({offering_id:$(this).data('id')}));});
$('.schedule-event').on('dragstart',function(e){e.originalEvent.dataTransfer.setData('text/plain',JSON.stringify($(this).data('record')));});
$('.edit-meeting').on('click',function(){editor($(this).closest('.schedule-event').data('record'));});
$('.schedule-slot').on('dragover',function(e){e.preventDefault();$(this).addClass('drag-over');}).on('dragleave',function(){$(this).removeClass('drag-over');}).on('drop',function(e){e.preventDefault();$(this).removeClass('drag-over');try{editor(JSON.parse(e.originalEvent.dataTransfer.getData('text/plain')),$(this).data('day'),$(this).data('time'));}catch{}});
$('#reset-meeting').on('click',function(){document.getElementById('meeting-editor').reset();$('#meeting-editor').attr('action','/academic/schedules');$('#meeting-form-title').text('Create meeting');$('#schedule-result').empty();});
$('#period-filter,#program-filter,#year-filter,#section-filter,#floor-filter,#room-filter,#day-filter').on('change',function(){
 const matches=(el,field,value)=>!value||String($(el).data(field))===String(value);
 $('.draggable-offering,.schedule-event').each(function(){let visible=matches(this,'period',$('#period-filter').val())&&matches(this,'program',$('#program-filter').val())&&matches(this,'year',$('#year-filter').val())&&matches(this,'section',$('#section-filter').val());if($(this).hasClass('schedule-event'))visible=visible&&matches(this,'floor',$('#floor-filter').val())&&matches(this,'room',$('#room-filter').val());$(this).toggle(visible);});
 $('.day-column').each(function(){$(this).toggle(matches(this,'day',$('#day-filter').val()));});
}).trigger('change');
$('#meeting-editor').on('submit',function(e){e.preventDefault();const form=$(this);const button=form.find('button[type="submit"],button:not([type])').first();button.prop('disabled',true);$('#schedule-result').empty();$.ajax({url:form.attr('action'),method:'POST',data:form.serialize(),headers:{Accept:'application/json'}}).done(function(){window.location.href='/scheduler';}).fail(function(xhr){const errors=xhr.responseJSON?.errors;const alert=$('<div class="alert alert-danger">');if(errors)Object.values(errors).flat().forEach(message=>alert.append($('<div>').text(message)));else alert.text('The schedule could not be saved. Refresh and try again.');$('#schedule-result').append(alert);}).always(function(){button.prop('disabled',false);});});
