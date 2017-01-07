var EcomailBackOffice = (function() {

  var hereDoc = function( f ) {
    return f.toString().replace( /^[^\/]+\/\*!?/, '' ).replace( /\*\/[^\/]+$/, '' );
  };

  var _module = {

    init: function( config ) {
      var config = jQuery.extend( {
        formFieldAPIKey:      null,
        formFieldList:        null,
        formFieldRowSelector: '.form-group',
        buttonConnect:        'ecConnect',
        ajaxUrl:              null,
        templates:            {
          connect: hereDoc( function() {/*!
<div class="form-group">
  <label class="control-label col-lg-3"></label>
  <div class="col-lg-9 ">
    <input type="submit" value="Připojit" id="{BUTTON_CONNECT}" class="btn">
  </div>
</div>
*/
          } )
        }
      }, config );

      var $ = jQuery;

      $( function() {
        var ecApiKey = $( '#' + config.formFieldAPIKey );
        var ecSubmitButton = $( '.panel-footer button[type="submit"]' );
        var ecSelectList = $( '#' + config.formFieldList );
        var ecSelectListGroup = ecSelectList.parents( config.formFieldRowSelector );

        var remoteFormShown = false;

        if( ecApiKey.val() == '' ) {
          ecInitRemoteForm();
        }

        ecApiKey.on( 'keyup', function() {
          ecInitRemoteForm();
        } );

        function ecInitRemoteForm() {
          if( remoteFormShown == false ) {
            ecSubmitButton.hide();
            ecSelectListGroup.hide();

            var html = config.templates.connect;
            html = html.replace( '{BUTTON_CONNECT}', config.buttonConnect );
            $( html ).insertAfter( ecApiKey.closest( config.formFieldRowSelector ) );
            $( '#' + config.buttonConnect ).on( 'click', function( e ) {

              var $this = $( this );

              e.preventDefault();

              $this.val( 'Připojuji...' );

              var params = {
                cmd: 'getLists', APIKey: ecApiKey.val()
              };
              if( window.FORM_KEY ) {
                params['form_key'] = window.FORM_KEY;
              }

              $.ajax( {
                url: config.ajaxUrl, data: params, type: 'get', dataType: 'json', success: function( data ) {
                  ecSelectList.html( '' );
                  $.each( data, function( key, val ) {
                    ecSelectList.append( '<option value="' + val.id + '">' + val.name + '</option>' );
                  } );
                  ecSelectListGroup.show();
                  ecSubmitButton.show();
                  $this.parents( config.formFieldRowSelector ).remove();
                  remoteFormShown = false;
                }
              } );
            } );

            remoteFormShown = true;
          }
        }
      } );
    }
  };

  return _module;

})();