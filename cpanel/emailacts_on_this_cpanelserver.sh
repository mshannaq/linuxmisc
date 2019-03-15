#!/bin/bash

#get a list of all of the email accounts on the server and what username they’re associated with
#https://serversitters.com/get-a-list-of-all-email-accounts-on-a-cpanel-server.html

#running user must be root uid 0
if [ "$EUID" -ne 0 ]
  then echo "Please run as root"
  exit 1
fi

#check if this is a cpanel server and users folder already exists
if [ ! -d /var/cpanel/users/ ] 
  then echo "Are you sure this is a Cpanel server?! becuase /var/cpanel/users/ is not a valid folder"
  exit 1
fi


OWNER=$@
KONTA=`ls -1A /var/cpanel/users/`

count=1
for x in `echo -n "$KONTA"`;do
wiersz=`grep -i ^dns /var/cpanel/users/"$x" |cut -d= -f2`
DOMAIN[$count]=$wiersz
count=$[$count+1]
echo "Login: `echo "$x"`"
for i in `echo "${DOMAIN[@]}" | sed 's/ /\n/g'`;do
for n in ` ls -A /home/"$x"/mail/"$i"/ 2>/dev/null`;do

if [ "$n" == "cur" ];then echo "$n" > /dev/null
elif [ "$n" == "new" ];then echo "$n" > /dev/null
elif [ "$n" == "tmp" ];then echo "$n" > /dev/null
elif [ "$n" == "" ];then echo "$n" > /dev/null
else
echo "$n"@"$i"
fi
done
done
echo;echo;
done







