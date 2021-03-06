(function($) {
   $.widget("ui.gallery_in_place_edit",  {
     _init: function() {
       var self = this;
       this.element.click(function(event) {
         event.preventDefault();
         self._show(event.currentTarget);
         return false;
       });
     },

     _show: function(target) {
       var self = this;
       var tag_width = $(target).width();
       $(self).data("tag_width", tag_width);

       var form = $("#g-in-place-edit-form");
       if (form.length > 0) {
         self._cancel();
       }

       $.get(self.options.form_url.replace("__ID__", $(target).attr('rel')), function(data) {
         var parent = $(target).parent();
         parent.children().hide();
         parent.append(data);
         parent.find("form :text")
           .width(tag_width)
           .focus();
         $(".g-short-form").gallery_short_form();
         parent.find("form .g-cancel").click(function(event) {
           self._cancel();
           event.preventDefault();
           return false;
         });
         self._ajaxify_edit();
       });

     },

     _cancel: function() {
       var parent = $("#g-in-place-edit-form").parent();
       $(parent).find("form").remove();
       $(parent).children().show();
       $("#g-in-place-edit-message").remove();
     },

     _ajaxify_edit: function() {
       var self = this;
       var form = $("#g-in-place-edit-form");
       $(form).ajaxForm({
         dataType: "json",
         success: function(data) {
           if (data.result == "success") {
             window.location.reload();
           } else {
             var parent = $(form).parent();
             $(form).replaceWith(data.form);
             var width = $(self).data("tag_width");
             $(parent).find("form :text")
               .width(width)
               .focus();
             $(".g-short-form").gallery_short_form();
             $(parent).find("form .g-cancel").click(function(event) {
               self._cancel();
               event.preventDefault();
               return false;
             });
             self._ajaxify_edit();
           }
         }
       });
     }
   });

   $.extend($.ui.gallery_in_place_edit,  {
     defaults: {}
   });
})(jQuery);
