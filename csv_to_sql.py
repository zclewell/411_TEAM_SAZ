import MySQLdb
import csv

db_login_f = open('secrets')
usern = db_login_f.readline()
passwd = db_login_f.readline()

db = MySQLdb.connect(host='localhost', user=usern, passwd='thisisapassword', db='teamsaz411_chicageo')

cursor = db.cursor()

with open('chi_crime.csv', 'r') as crime_csv:
    reader = csv.reader(crime_csv)
    cols = next(reader)
    query = "INSERT INTO crime_point({0}) VALUES ({1})"
    query = query.format(','.join(cols[1:]), ','.join('%s' for _ in range(1,len(cols))))
    print(query)
    for data in reader: 
        data[1] = data[1].split("+")[0]
        print(query, data[1:])
        if len(data[1:]) == 4 and len(data[2]) > 0 and len(data[3]) > 0:
            cursor.execute(query, data[1:])
        db.commit()

db.close()

db_login_f.close()

