# sudo tail /var/log/apache2/error.log

# git status
# git add .
# git commit -m ""
# git push

template="test"

echo "Template: ${template}"

get_timestamp()
{
  date +%s
}

timestamp=$(get_timestamp)

temp_file_js=$(mktemp)
temp_file_css=$(mktemp)

browserify bundle.js | uglifyjs >> public/assets/${template}_${timestamp}.js
uglifyjs app/templates/${template}/template.js >> public/assets/${template}_${timestamp}.js

#browserify bundle.js | uglifyjs >> $temp_file_js
#uglifyjs app/templates/${template}/template.js >> $temp_file_js

minify app/templates/${template}/bulma.css >> public/assets/${template}_$timestamp.css
minify app/templates/${template}/template.css >> public/assets/${template}_$timestamp.css
minify app/templates/${template}/arimo.css >> public/assets/${template}_$timestamp.css

#minify app/templates/${template}/bulma.css >> $temp_file_css
#minify app/templates/${template}/template.css >> $temp_file_css
#minify app/templates/${template}/arimo.css >> $temp_file_css

#cp $temp_file_js public/assets/${template}_${timestamp}.js
#cp $temp_file_css public/assets/${template}_$timestamp.css