    var readingsTable = $('#program-readings').dataTable({
        "bPaginate": false,
        "bLengthChange": false,
        "aoColumns": [
            { "bSortable": false },
            { "bSortable": false },
            { "bSortable": false },
            { "bSortable": false },
            { "bSortable": false },
            { "bSortable": false },
            { "bSortable": false }
        ],
        "bJQueryUI": true,
        "bInfo":false
    });

    var tagsOr=true;
    var $tagFilterButtons=false, $courseFilterButtons=false, $readingRows=false, $readingRowsA=false;
    
    $('body')
    .on('click','.course-button',function(){
    	var $this=$courseFilterButtons.filter('.'+$(this).data('filter'));
    	active=($this.data('active')==='true');
    	$this.data('active',active?'false':'true');
    	updateDisplay();
    })
    .on('click','.tag-button',function(){
      var $this=$tagFilterButtons.filter('.'+$(this).data('filter'));
      active=($this.data('active')==='true');
      $this.data('active',active?'false':'true');
      updateDisplay();
    });
    
    $('.btn-modifier').on('click', function(){
        $('.btn-sort-modifiers > .btn-modifier').each(function(){
            $(this).removeClass('btn-success').addClass('btn-inverse');
        });
        $(this).addClass('btn-success').removeClass('btn-inverse');
        var sort = this.getAttribute('data-sort');
        tagsOr = (sort == 'any');
        updateDisplay();
    });
    
    
    function updateDisplay(){
    	  var activeCourses=[],
    	      activeTags=[],
    	      selector=[],
    	      tag='',i=0,j=0,tagsOr=window.tagsOr;
        $courseFilterButtons.each(function(){
            var $this=$(this),
                active=($this.data('active')==='true'),
                tag=$this.data('filter').replace(/\s+$/,'');
            if(active){
              activeCourses.push(tag);
            }
          });
        $tagFilterButtons.each(function(){
            var $this=$(this),
                active=($this.data('active')==='true'),
                tag=$this.data('filter').replace(/\s+$/,'');
            if(active){
              activeTags.push(tag);
            }
          });
        $courseFilterButtons.removeClass('btn-tag-alt-active-course');
        $tagFilterButtons.removeClass('btn-tag-alt-active-tag');
        $tagFilterButtons.hide();
        if(activeCourses.length>0){
          $courseFilterButtons.filter('.'+activeCourses.join(',.')).addClass('btn-tag-alt-active-course');
          $tagFilterButtons.filter('.'+activeCourses.join(',.')).show();
        }else{
        	activeCourses=courseTags;
          $tagFilterButtons.show();
        }
        if(activeTags.length>0){
        	$tagFilterButtons.filter('.'+activeTags.join(',.')).addClass('btn-tag-alt-active-tag');
	        if(tagsOr){
	        	for(i=0;i<activeCourses.length;i++){
	        		for(j=0;j<activeTags.length;j++){
	        			selector.push('.'+activeCourses[i]+'.'+activeTags[j]);
	        		}
	        	}
	        }else{
	            for(i=0;i<activeCourses.length;i++){
	                  selector.push('.'+activeCourses[i]+'.'+activeTags.join('.'));
	              }
	        }
        }else{
        	selector=$.map(activeCourses,function(tag){return '.'+tag;});
        }
        $readingRows.hide();
        selector=selector.join(',');
        $readingRows.filter(selector).show();
        $('#reading-filter-count').text($readingRows.filter(':visible').size());
        $readingRows.filter(':visible:odd').removeClass('even').addClass('odd');
        $readingRows.filter(':visible:even').removeClass('odd').addClass('even');
    }
 
    $('.open-program-reading').on('click', function () {
        $(".additional_url").remove();
        var data = this.dataset;
        if(!data){
        	data={
        			get:this.getAttribute('data-get'),
        			title:this.getAttribute('data-title'),
        			author:this.getAttribute('data-author'),
        			calln:this.getAttribute('data-calln'),
        			loanp:this.getAttribute('data-loanp'),
        			tags:this.getAttribute('data-tags'),
        			urls:this.getAttribute('data-urls')
        				
        	};
        }
        $('#insert-reading-get').attr('href', data.get);
        $('#insert-reading-title').text(data.title);
        $('#insert-reading-author').text(data.author);
        $('#insert-reading-calln').text(data.calln);
        $('#insert-reading-loanp').text(data.loanp);
        $('#insert-reading-tags').text(data.tags);

        var str ='';
        if(data.urls !== ""){
            $.each($.parseJSON(data.urls), function (key, value) {
                str += '<div class ="row-fluid expand additional_url"><div class="span8 program-item-url"><p>' + value.description + ' (' + value.format + ')</p></div><div class="span4"><a href = "' + value.url + '"class="btn btn-info" target="_blank">View Supplemental&nbsp;<i class="fa fa-external-link-square"></i></a></div> </div>';
            });
        }

        $('#program-reading').reveal({
            animation: 'fade',
            animationspeed: 50,
            closeonbackgroundclick: false,
            dismissmodalclass: 'close-reveal-modal',
            open: function () {
                if (str !== '') {
                    $('#additional-urls-area').show();
                    $('#place-additional-urls-after').after(str);
                } else {
                    $('#additional-urls-area').hide();
                }
            },
            opened: function () {
                var dialogHeight = window.innerHeight ? window.innerHeight : $(window).height();
                dialogHeight -= 50;
                if(dialogHeight > 500) {
                    dialogHeight = 500;
                }
                $(this).height(dialogHeight);
            },
            close: function () {
                $(".additional_url").remove();
            }
        });
    });
