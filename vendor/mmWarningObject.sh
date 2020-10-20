#!/bin/bash
# These functions update the warning_object files in the Muximux main folder.
# Admin updates the admin section and allows Admin to post relivant data for users.
# System update the System section and allows for automated information about system 
#       information and performance, etc.
#
function _muximuxSystemMessage {
# Usage: _muximuxSystemMessage "System" "$( _getExpireSeconds $num $zone )" "$message"
# 		 $1 = Admin, System
#        $2 = Expiry date in seconds
#        $3 = Message text.
#        $4 = Q || q, [optional] quiet mode
#        boundries: <!--StartOfAdmin--> <!--EndOfAdmin--> <!--StartOfSystem--> <!--EndOfSystem-->
#        grep get line number: grep -n "pattern" file.txt | grep -Eo '^[^:]+'
    local webFolder='/var/www/html/mm'
    local monDay="$(date  "+%d-%T")"
    local prefix="!--$2-->"
    local SoAline
    local SoSline
    local woafile="${webFolder}/warning_object_admin.txt"
    local wosfile="${webFolder}/warning_object_system.txt"
    local wofile="${webFolder}/warning_object_combined.html"
    local parts=()
	local str="$monDay $3"
cd ${webFolder} || exit 1
if [[ "$1" == "Admin" ]]; then
    SoAline=$( grep -n "<!--9999999990-->" $woafile | grep -Eo '^[^:]+' )
    EoAline=$( grep -n "<!--0000000010-->" $woafile | grep -Eo '^[^:]+' )
    if ( [ "$SoAline" = "12345" ] || [ "$EoAline" = "12345" ] ); then
        echol $( cat $woafile )
echo '<!--9999999999--><head><meta http-equiv="refresh" content="60" ><meta http-equiv="refresh" content="1800"><base target="_blank" /></head>
<!--9999999998--><body style="margin: 2px">
<!--9999999997--><p style="color: orange;">
<!--9999999996--><u style="color: OrangeRed">Admin:</u><br/>
<!--9999999990-->
<!--0000000010--> 
<!--0000000000-->' > $woafile
    fi
    ( [ "$4" = "q" ] || [ "$4" = "Q" ] ) || echol "<code>Admin: &lt;$prefix$str S=$SoAline, E=$EoAline</code>"
	sed "$(( $SoAline + 1))""i""<$prefix$str<br>" $woafile > $woafile.temp
    sort -r -k  1n $woafile.temp > $woafile
fi
if [[ "$1" == "System" ]]; then
	SoSline=$( grep -n "<!--9999999990-->" $wosfile | grep -Eo '^[^:]+' )
    EoSline=$( grep -n "<!--0000000010-->" $wosfile | grep -Eo '^[^:]+' )
    if ( [ "$SoSline" = "" ] || [ "$EoSline" = "" ] ); then
echo '<!--9999999999--><head><base target="_blank" /></head>
<!--9999999998--><body style="margin: 2px">
<!--9999999997--><p style="color: orange;">
<!--9999999996--><u style="color: OrangeRed">System:</u><br>
<!--9999999990-->
<!--0000000010--> 
<!--0000000000--></body></html>' > $wosfile
fi
    ( [ "$4" = "q" ] || [ "$4" = "Q" ] ) || echol "<code>System: &lt;$prefix$str S=$SoSline, E=$EoSline</code>"
	sed  "$(( $SoSline + 1 ))""i""<$prefix$str<br>" $wosfile > $wosfile.temp
    sort -r -k  1n $wosfile.temp > $wosfile
fi
cat $woafile $wosfile > $wofile 
rm -f "${webFolder}/$woafile.temp"
rm -f "${webFolder}/$wosfile.temp"
} 
###########################
function _muximuxClearOutdated {
    local webFolder='/var/www/html/mm'
    local woafile="${webFolder}/warning_object_admin.txt"
    local wosfile="${webFolder}/warning_object_system.txt"
	local nowDate=$( date +%s)
    local lineDate
	local curLine="1"
while IFS= read -r line
do
  #echo "date: ${line:4:10}"
  # get text of just important lines: echo $( head -n $last $mmfile | tail -n $(( $last - $first )) )
  lineDate=${line:4:10}; 
  lineDate="$( echo "$lineDate" | sed 's/[^0-9]*//g' )"
  [ "${#lineDate}" -lt "10" ] && continue
  [ "$lineDate" -gt "10" ] && [ "$lineDate" -lt "$nowDate" ] && sed -i "$curLine""d" $woafile && curLine=$(( curLine - 1 )) 
  curLine=$(( curLine + 1 ))
done < "$woafile"
#
curLine="1"
while IFS= read -r line
do
  #echo "date: ${line:4:10}"
  # get text of just important lines: echo $( head -n $last $mmfile | tail -n $(( $last - $first )) )
  lineDate=${line:4:10}; 
  lineDate="$( echo "$lineDate" | sed 's/[^0-9]*//g' )"
  [ "${#lineDate}" -lt "10" ] && continue
  # [ "$lineDate" -gt "10" ] && [ "$lineDate" -lt "$nowDate" ] && echol "${line:4:10}, $( echo "$lineDate" | sed 's/[^0-9]*//g' ), len=${#lineDate},  $lineDate -lt $nowDate"
  [ "$lineDate" -gt "10" ] && [ "$lineDate" -lt "$nowDate" ] && sed -i "$curLine""d" $wosfile  && curLine=$(( curLine - 1 ))
  curLine=$(( curLine + 1 ))
done < "$wosfile"
sleep 1
}
function echol {
    OIFS=$IFS
  IFS=" " # needed for "$*"
  #printf "$(date +"$dfmt").$n_code$ver $*\n" | tee >( sed 's/\x1B\[[0-9;]*[JKmsu]//g' >> $LOGF )
  # printf "$(date +"$dfmt").$n_code$ver $*\n" >> $LOGF
  printf "$(date +"$dfmt").$n_code$ver $*\n" 2>&1 | tee -a $LOGF
  IFS=$OIFS
  [ $(( $(date +%s) % 20 )) -eq 0 ] && _startedStuckCheck; RET=$?
}