#!/bin/sh
#.Updated Date : 2014/03/06
#.Purpose      : 備份MySQL並傳回遠端，只在本機保留幾天份的備份檔
#.Usage        : sh backupMySQL_2.sh
#=========================================================
# 2013/12/20 : 取消 johoo_group 備份
# 2014/03/06 : 增加 c2, my85 備份
#=========================================================

#+User defined variables.
DBs="information_schema POLO2U innodb_buffer japanimport jojo_db moganship myb myday_main mygroup mygroupkr mysql newgomall ntut_121 test yeslife z-master c2 my85"     #The databases wanted to back up and spareting them by space, for ex
ample, "testdb1 testdb2 ......."
BACKUP_DIRECTORY="/opt/admin/mysqlBackup"       #The directory wanted to store the backing up files. Please use absolute path.
DB_ACCOUNT="db_backup"                          #MySQL account.
DB_PASSWORD="bkxx@@911"                         #The password of the MySQL account.
TARGET_SERVER=""                                #The MySQL server wanted to back up, and keep it blank if the server is local.
ROATING_PERIOD="7"                              #Rotating period is base on day. If it is less than 1, the rotating will be disabled.
COMPRESS_FLAG="1"                               #Compress the backup .sql files if the COMPRESS_FLAG is not 0.
SCP_ACCOUNT="backup"                            #The account use to scp the backup files. 
SCP_DESTINATION_IP="tw.myday.com.tw"            #Keep it nothing if do't want to scp the backup files to remote server.
SCP_PATH="/opt/admin/backupFiles/mysql"         #The path where the backup files are stored in the remote server.
SCP_PORT="7788"                                 #The scp port.
SCP_FLAGS="-r -i /home/keke/.ssh/id_rsa"

#.2013/05/29:  add funcitons for : 1. check backup status.  2.send the status mail 
MAIL_GROUP="ivan_teng@myday.com.tw,harry@myday.com.tw"  #要將狀態寄給哪些人，以逗號加空白做區隔
MAIL_CONTENT="Backup Status:\n"                 #信件內容

#+Environment variables.
PATH="/usr/kerberos/sbin:/usr/kerberos/bin:/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin:/root/bin"
export PATH

#===Please don't change the following code casually.=======

#+Defined functions.
#.Function Name : convertFileNameDateToSeconds()
#.Purpose : Convernt the date format yyyy-mm-dd to the second difference from 1970/01/01 00:00:00(UTC) defined by date command.
#.Parameters : $1 : The date which is wanted to convert and its format is 'yyyy-mm-dd'.
function convertFileNameDateToSeconds(){
        #Convert date format from yyyy-mm-dd to yyyy/mm/dd.
        DATE=`echo "$1" | awk -F"-" '{print $1"/"$2"/"$3}'`
        #Give it the default time : 00:00:00.
        echo `date -d "$DATE 00:00:00" +%s`
}

#+Defined variables.
BACKUP_DATE=`date "+%Y-%m-%d"`                                          #The backup date for the file name.
BACKUP_DATE_SECONDS=$(convertFileNameDateToSeconds $BACKUP_DATE)        #The backup seconds from 1970/01/01 00:00:00(UTC) defined by date command.
SPARATING_WORD="_"                                                      #Sparate user defined file name and fixed file name format.
BACKUP_FLAGS="--quick --lock-tables=false"                              #The options of mysqldump
if [ "X"$TARGET_SERVER != "X" ]; then                                   #Connect to MySQL remotely or not.
        BACKUP_FLAGS="-h $TARGET_SERVER $BACKUP_FLAGS"
fi
ROATING_PERIOD_SECOND=`expr \( $ROATING_PERIOD + 1 \) \* 86400`         #Convert rotating period unit from day to second.

#1.)Check if the directory wanted store the backing up files exists.
if [ ! -d $BACKUP_DIRECTORY/$BACKUP_DATE ]; then
        mkdir -p $BACKUP_DIRECTORY/$BACKUP_DATE
fi

#2.)Back up these DB by mysqldump.
MAIL_CONTENT=$MAIL_CONTENT"`date`: Start Dumping MySQL.\n"
for DB in $DBs; do
        mysqldump -u$DB_ACCOUNT -p$DB_PASSWORD $BACKUP_FLAGS --database $DB > "$BACKUP_DIRECTORY/$BACKUP_DATE/$DB.sql"
        #.2013/05/29: added for checking the mysqldump status.
        if [ $? -ne 0 ]; then
                MAIL_CONTENT=$MAIL_CONTENT"ALERT!!!!!! ===> \t`date`:Dumping $DB is fail.\n"
        else
                MAIL_CONTENT=$MAIL_CONTENT"\t`date`:Dumping $DB is ok.\n"
		fi
done

#3.)Compress the backup files.
MAIL_CONTENT=$MAIL_CONTENT"`date`: Start packaging the files.\n"
if [ $COMPRESS_FLAG -ne 0 ]; then
        ORGINAL_PATH=`pwd`
        cd $BACKUP_DIRECTORY/$BACKUP_DATE/
        tar zcvf $BACKUP_DATE.tgz ./*.sql --remove-files
        md5sum $BACKUP_DATE.tgz > ./md5checksum.txt
        cd $ORGINAL_PATH
fi

#4.)SCP the backup files to remote server.
MAIL_CONTENT=$MAIL_CONTENT"`date`: Start scp the package.\n"
if [ "X"$SCP_DESTINATION_IP != "X" ]; then
        scp $SCP_FLAGS -P $SCP_PORT -l 3000 $BACKUP_DIRECTORY/$BACKUP_DATE $SCP_ACCOUNT@$SCP_DESTINATION_IP:$SCP_PATH
        #.2013/05/29: added for checking the scp status and file correctness.
        if [ $? -ne 0 ]; then
                MAIL_CONTENT=$MAIL_CONTENT"ALERT!!!!!! ===> \t`date`:Scp to $SCP_ACCOUNT@$SCP_DESTINATION_IP:$SCP_PATH is fail.\n"
        else
                MAIL_CONTENT=$MAIL_CONTENT"\t`date`:Scp to $SCP_ACCOUNT@$SCP_DESTINATION_IP:$SCP_PATH is ok.\n"
                FILE_CORRECTNESS=`ssh -p $SCP_PORT $SCP_ACCOUNT@$SCP_DESTINATION_IP "cd $SCP_PATH/$BACKUP_DATE; md5sum -c md5checksum.txt|grep -i -E 'OK|正確' |wc -l;"`
                if [ $FILE_CORRECTNESS -ne 1 ]; then
                        MAIL_CONTENT=$MAIL_CONTENT"ALERT!!!!!! ===> \t`date`:File in $SCP_PATH/$BACKUP_DATE is not correct.\n"
                else
                        MAIL_CONTENT=$MAIL_CONTENT"\t`date`:File in $SCP_PATH/$BACKUP_DATE is correct.\n"
                fi
        fi
fi

#5.)Rotate the backing up files.
if [ $ROATING_PERIOD -ge 1 ]; then
        #3-1)Find out all the backup directory.
        DIR_ITEMS=`find $BACKUP_DIRECTORY -maxdepth 1 -type d | grep "$BACKUP_DIRECTORY/" | sort | awk -F "$BACKUP_DIRECTORY/" '{print $2}'`
        #3-2)Rotate with the date which is the directories' name.
        for DIR_ITEM in $DIR_ITEMS; do
                DIR_SECONDS=$(convertFileNameDateToSeconds $DIR_ITEM)
                TIME_DIFFERENCE=`expr $BACKUP_DATE_SECONDS - $DIR_SECONDS`
                if [ $ROATING_PERIOD_SECOND -le $TIME_DIFFERENCE ]; then
                        rm -rf $BACKUP_DIRECTORY/$DIR_ITEM
                        #.2013/05/29: added for deling file.
                        MAIL_CONTENT=$MAIL_CONTENT"\t`date`:Delete the backup file, $BACKUP_DIRECTORY/$DIR_ITEM, on DB server.\n"
                fi
        done
fi

MAIL_CONTENT=$MAIL_CONTENT"`date`: End."

#6.)2013/05/29: added for sending mail
echo -e $MAIL_CONTENT | mail -s "mysqldump status on `date "+%T %Y-%m-%d (%Z)"`" $MAIL_GROUP