function isValidFilename(fname) {
  var invalid_char = new Array("/","\"","<",">","|",":");
  for (var i=0;i<invalid_char.length;i++) {
    if (fname.indexOf(invalid_char[i])!=-1) {
      return false;
    }
  }
  return true;
}
function isIP(ip) {
  var cnt=0;
  if ( ip.indexOf(".")!=-1 ) {
    ips=ip.split(".");
    for (var i=0;i<ips.length;i++) {
      if ( isInt(ips[i])  && ips[i]>=0 && ips[i]<=255 ) cnt++;
      }
    }
  if ( cnt==4 ) return true;
  else return false;
  }
<!-- �ˬd�γr����j���h�� e-Mail -->
function isMmail(mail) {
  var cnt=0;
  if ( mail.indexOf(",")!=-1 ) {
    sstr=mail.split(",");
    for (var i=0;i<sstr.length;i++) {
      cnt+=isMail(sstr[i]);
      }
    }
  if ( mail.indexOf(",")==-1 ) {
    cnt=isMail(mail);
    }
  if ( cnt>0 ) return false;
  else return true;
  }
function isMail(mail) {
  if ( mail=="" ) return 1;
  var emailReg ="^[\\w-_\.]*[\\w-_\.]\@[\\w]\.+[\\w]+[\\w]$";
  var regex = new RegExp(emailReg);
  if (regex.test(mail)) return 0;
  else return 1;
  }
function isInt(num) {
  var numer = num;
  var numReg ="^[0-9]+$";
  var regex = new RegExp(numReg);
  if( regex.test(numer) ) {
    return true;
    }
  else {
    return false;
    }
  }

//isDate functions
var dtCh= "-";
var minYear=1900;
var maxYear=2200;

function isInteger(s){
        var i;
    for (i = 0; i < s.length; i++){
        // Check that current character is number.
        var c = s.charAt(i);
        if (((c < "0") || (c > "9"))) return false;
    }
    // All characters are numbers.
    return true;
}

function stripCharsInBag(s, bag){
        var i;
    var returnString = "";
    // Search through string's characters one by one.
    // If character is not in bag, append to returnString.
    for (i = 0; i < s.length; i++){
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1) returnString += c;
    }
    return returnString;
}

function daysInFebruary (year){
        // February has 29 days in any year evenly divisible by four,
    // EXCEPT for centurial years which are not also divisible by 400.
    return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
}
function DaysArray(n) {
        for (var i = 1; i <= n; i++) {
                this[i] = 31
                if (i==4 || i==6 || i==9 || i==11) {this[i] = 30}
                if (i==2) {this[i] = 29}
   }
   return this
}

function isDate(dtStr){
        var daysInMonth = DaysArray(12)
        var pos1=dtStr.indexOf(dtCh)
        var pos2=dtStr.indexOf(dtCh,pos1+1)
        var strYear=dtStr.substring(0,pos1)
        var strMonth=dtStr.substring(pos1+1,pos2)
        var strDay=dtStr.substring(pos2+1)
        strYr=strYear
        if (strDay.charAt(0)=="0" && strDay.length>1) strDay=strDay.substring(1)
        if (strMonth.charAt(0)=="0" && strMonth.length>1) strMonth=strMonth.substring(1)
        for (var i = 1; i <= 3; i++) {
                if (strYr.charAt(0)=="0" && strYr.length>1) strYr=strYr.substring(1)
        }
        month=parseInt(strMonth)
        day=parseInt(strDay)
        year=parseInt(strYr)
        if (pos1==-1 || pos2==-1){
                //alert("The date format should be : mm/dd/yyyy")
                return false
        }
        if (strMonth.length<1 || month<1 || month>12){
                //alert("Please enter a valid month")
                return false
        }
        if (strDay.length<1 || day<1 || day>31 || (month==2 && day>daysInFebruary(year)) || day > daysInMonth[month]){
                //alert("Please enter a valid day")
                return false
        }
        if (strYear.length != 4 || year==0 || year<minYear || year>maxYear){
                //alert("Please enter a valid 4 digit year between "+minYear+" and "+maxYear)
                return false
        }
        if (dtStr.indexOf(dtCh,pos2+1)!=-1 || isInteger(stripCharsInBag(dtStr, dtCh))==false){
                //alert("Please enter a valid date")
                return false
        }
return true
}

