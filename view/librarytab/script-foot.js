$('#tab a').on('click',function (e) {
  e.preventDefault();
  if($(this).hasClass('disabled')){
    return false;
  }
  $(this).tab('show');
});

$('#tab a:first').tab('show');
$('.current0').addClass('hidden');
$(document).on('keyup', '#csearch', performSearch)
.on('change', '#currentonly', performSearch)
.on('change', '#studentcurrentonly',
  function(){
    $('.current0').toggleClass('hidden');
  }
);
if($('#prevsearches tr').size()>0){
  $('#prevsearches_head').show();
  $('#prevsearches').show();
}else{
  $('#tab a:last').addClass('disabled');
}
function performSearch() {
  var searchfor = $('#csearch').val();
  if (searchfor.length > 4) {
    $('#courselist_head').show();
    $('#courselist').show();
    $.ajax({
      type : 'GET',
      url : baseurl+'/passtolicr.php',
      data : {
        'command' : 'SearchCourses',
        'search_string' : searchfor,
        'current' : ($('#currentonly').get(0).checked? 1:0),
        'activeonly':1
      },
      dataType : 'json'
    }).done(
        function(data) {
          var i = 0, html = '';
          if (data.success && data.data.length > 0) {
            $('#numresults').text(data.data.length+' results.');
            for (i = 0; i < data.data.length; i++) {
              html += '<tr><td><a href="' + baseurl + '/instructorhome/id/'
                  + data.data[i].course_id + '">' + data.data[i].title
                  + '</a></td><td>' + data.data[i].location + '<br />'
                  + data.data[i].branch + '</td><td>' + data.data[i].visible
                  +'/'+data.data[i].total
                  + '</td></tr>';
            }
          } else {
            $('#numresults').text('No results.');
            html = '';
          }
          $('#courselist').html(html);
        });
  }else if(searchfor.length==0){
    $('#courselist_head').hide();
    $('#courselist').hide();
    $('#numresults').text('Waiting for search.');
  }else{
    $('#numresults').text('Search requires at least 5 characters.');
  }
}

$('#courselist').on('click touchend', 'a', function() {
  var $this = $(this);
  var id = $this.attr('href').match(/([0-9]*)$/)[0];
  $('#prevsearches_head').show();
  $('#prevsearches').show();
  $('#tab a:last').removeClass('disabled');
  $.ajax({
    type : 'POST',
    url : baseurl + '/librarytab.stashsearch',
    data : {
      course_id : id,
      session_id : sessionid
    },
    success : function() {
/*
      $this.closest('tr').remove();
      $('#prevsearches').append(
          '<tr><td><i class="icon icon-remove clearprevioussearch" data-class_id="'+
          id+'"></i> '+
          $this.closest('tr').html().replace(/^<td>/,'')
          +'</tr>'
          
          );
*/          
      document.location=$this.attr('href');
    }
  });
  return false;
});

$('#prevsearches').on('click touchend', '.clearprevioussearch', function() {
  var $this = $(this);
  var id = $this.data('class_id');
  $.ajax({
    type : 'POST',
    url : baseurl + '/librarytab.clearstash',
    data : {
      course_id : id,
      session_id : sessionid
    },
    success : function() {
      $this.closest('tr').remove();
      if($('#prevsearches tr').size()==0){
        $('#prevsearches_head').hide();
        $('#prevsearches').hide();
        $('#tab a:last').addClass('disabled');
      }
    }
  });
});

$('#clr').click(function(){$('#csearch').val('');performSearch();});
$('#top10 a').attr('target', '_blank');
$('a[href*=colorbox]').removeAttr('target').colorbox({
      href:$('a[href*=colorbox]').attr('href'),
      iframe:true,
      height:'80%',
      width:'80%'
    }).click(function(){$('#colorbox').toggle();});
