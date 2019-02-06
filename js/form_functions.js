(function($) {
    $.fn.showErrorCheckmark = function(el) {
        $("label[for="+el+"] .checkmark").css("border", "1px solid red");
    }
    $.fn.showErrorEmail = function(el) {
        $("#" + el).css("background-color", "red");
        $("#" + el).css("color", "white");
    }

    function getUrlVars()
    {
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for(var i = 0; i < hashes.length; i++)
        {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    }

    Drupal.behaviors.getVotes = {
        attach: function (context, settings) {
            $(window).once().on('load', function () {
                $.get('/likeajax_votes/', { code: getUrlVars()["code"] }, responseVotes);
            });
          }
      }

      var responseVotes = function(response) {
        var votes = response[1].text;
        $('#ajax-target').html(response[0].text);
        for(var i = 0; i < votes.length; i++) {
            $("#ajax-target").append("<br>Dyplom: " + votes[i].dyplom + " głosy " + votes[i].votes);
            $(".tile[data-idnode=" + votes[i].dyplom + "]").attr("data-votes", votes[i].votes);
        }
      }
      
}) (jQuery)