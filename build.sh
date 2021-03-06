# Use this script to minimize JS and CSS

# View error log:
# sudo tail /var/log/apache2/error.log

# View access log:
# sudo tail /var/log/apache2/access.log

# Enable redirect logging in .htaccess:
# LogLevel alert rewrite:trace3

# Flush MemCached:
# echo "flush_all" | nc -q 2 localhost 11211

# Restart MemCached:
# /etc/init.d/memcached restart

# Commit to GitHub:
# git status
# git add .
# git status
# git commit -m ""
# git push

template="default"

if  [[ -n "$1" ]]; then template=$1; fi

echo "Template: ${template}"

get_timestamp()
{
  date +%s
}

timestamp=$(get_timestamp)

temp_file_js=$(mktemp)
temp_file_css=$(mktemp)

browserify app/templates/${template}/bundle.js | uglifyjs >> $temp_file_js
uglifyjs app/templates/${template}/template.js >> $temp_file_js

#uglifycss app/templates/${template}/bulma.css >> $temp_file_css
#uglifycss app/templates/${template}/template.css >> $temp_file_css
#uglifycss app/templates/${template}/arimo.css >> $temp_file_css
lessc app/templates/${template}/bundle.less >> $temp_file_css

cp $temp_file_js public/assets/${template}_${timestamp}.js
cp $temp_file_css public/assets/${template}_${timestamp}.css
chmod 777 public/assets/${template}_${timestamp}.js
chmod 777 public/assets/${template}_${timestamp}.css