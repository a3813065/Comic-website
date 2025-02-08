import os
import paramiko
import mysql.connector
import stat

# SFTP 連接設置
sftp_host = '你的SFTP網址或IP'
sftp_port = 你的SFTP端口
sftp_user = '你的SFTP用戶名'
sftp_password = '你的SFTP密碼'

# MySQL 連接設置
mysql_host = '你的數據庫網址或IP'
mysql_port = 你的數據庫端口
mysql_user = '你的數據庫用戶名'
mysql_password = '你的數據庫密碼'
mysql_database = '你的數據庫名'

# 連接到 MySQL 數據庫
conn = mysql.connector.connect(
    host=mysql_host,
    port=mysql_port,
    user=mysql_user,
    password=mysql_password,
    database=mysql_database
)
cursor = conn.cursor()

# 連接到 SFTP 伺服器
transport = paramiko.Transport((sftp_host, sftp_port))
transport.connect(username=sftp_user, password=sftp_password)
sftp = paramiko.SFTPClient.from_transport(transport)

# 設定根目錄
root_dir = '/var/www/html/uploads'

def normalize_path(path):
    return path.replace("\\", "/")

# 遍歷根目錄下的所有子資料夾
for subfolder in sftp.listdir(root_dir):
    subfolder_path = os.path.join(root_dir, subfolder)
    subfolder_path = normalize_path(subfolder_path)

    try:
        file_attr = sftp.stat(subfolder_path)
        if stat.S_ISDIR(file_attr.st_mode):  # 確保是資料夾
            cursor.execute("SELECT id FROM comics WHERE cover_image = %s", (f"uploads/{subfolder}/",))
            comic = cursor.fetchone()

            if not comic:
                print(f"新增漫畫 {subfolder} 到 comics 表")
                cursor.execute(
                    "INSERT INTO comics (title, cover_image, static) VALUES (%s, %s, %s)",
                    (subfolder, f"uploads/{subfolder}/", 1)
                )
                conn.commit()
                cursor.execute("SELECT id FROM comics WHERE title = %s", (subfolder,))
                comic_id = cursor.fetchone()[0]
            else:
                comic_id = comic[0]
            
            # 檢查 chapters 表是否有該漫畫的章節
            cursor.execute("SELECT id FROM chapters WHERE comic_id = %s", (comic_id,))
            chapter = cursor.fetchone()

            if not chapter:
                subfolder_count = len(sftp.listdir(subfolder_path))
                cursor.execute(
                    "INSERT INTO chapters (comic_id, chapter_number) VALUES (%s, %s)",
                    (comic_id, subfolder_count)
                )
                conn.commit()
                cursor.execute("SELECT id FROM chapters WHERE comic_id = %s", (comic_id,))
                chapter_id = cursor.fetchone()[0]
            else:
                chapter_id = chapter[0]
            
            for sub_subfolder in sftp.listdir(subfolder_path):
                sub_subfolder_path = os.path.join(subfolder_path, sub_subfolder)
                sub_subfolder_path = normalize_path(sub_subfolder_path)

                try:
                    sub_subfolder_attr = sftp.stat(sub_subfolder_path)
                    if stat.S_ISDIR(sub_subfolder_attr.st_mode):
                        chapter_id_value = sub_subfolder
                        image_url_value = f"{sub_subfolder}/"

                        cursor.execute(
                            "SELECT 1 FROM chapter_images WHERE comic_id = %s AND chapter_id = %s AND image_url = %s",
                            (comic_id, chapter_id_value, image_url_value)
                        )
                        existing_entry = cursor.fetchone()

                        if not existing_entry:
                            print(f"新增 chapter_images：comic_id={comic_id}, chapter_id={chapter_id_value}, image_url={image_url_value}")
                            cursor.execute(
                                "INSERT INTO chapter_images (comic_id, chapter_id, image_url) VALUES (%s, %s, %s)",
                                (comic_id, chapter_id_value, image_url_value)
                            )
                            conn.commit()
                except FileNotFoundError:
                    print(f"警告：資料夾 {sub_subfolder_path} 不存在")
                    continue

    except Exception as e:
        print(f"處理資料夾 {subfolder_path} 時出錯: {e}")

sftp.close()
transport.close()
cursor.close()
conn.close()

print("操作完成！")