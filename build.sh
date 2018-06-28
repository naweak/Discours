# Use this script to minimize JS and CSS

# View error log:
# sudo tail /var/log/apache2/error.log

# Commit to GitHub:
# git status
# git add .
# git status
# git commit -m ""
# git push

template="test"

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

uglifycss app/templates/${template}/bulma.css >> $temp_file_css
uglifycss app/templates/${template}/template.css >> $temp_file_css
uglifycss app/templates/${template}/arimo.css >> $temp_file_css

cp $temp_file_js public/assets/${template}_${timestamp}.js
cp $temp_file_css public/assets/${template}_${timestamp}.css
chmod 777 public/assets/${template}_${timestamp}.js
chmod 777 public/assets/${template}_${timestamp}.css